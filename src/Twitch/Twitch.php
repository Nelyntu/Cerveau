<?php

namespace Twitch;

use GhostZero\Tmi;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;

class Twitch
{
    protected CommandDispatcher $commands;
    /** @var string[] */
    private array $badWords = [];
    protected ?ConnectionInterface $connection = null;
    protected bool $running = false;
    private Tmi\Client $ircClient;
    private LoggerInterface $logger;

    /**
     * @param mixed[] $options
     */
    public function __construct(
        Tmi\Client $ircClient,
        LoggerInterface $logger,
        array $options,
        CommandDispatcher $commandDispatcher
    ) {
        if (PHP_SAPI !== 'cli') {
            trigger_error(
                'Cerveau will not run on a webserver. Please use PHP CLI to run a Cerveau self-bot.',
                E_USER_ERROR);
        }

        if (is_array($options['badwords'])) {
            $this->badWords = $options['badwords'];
        }
        $this->commands = $commandDispatcher;
        $this->logger = $logger;
        $this->ircClient = $ircClient;
    }

    public function run(): void
    {
        $this->logger->info('[T][RUN]');
        if (!$this->running) {
            $this->running = true;
            $this->connect();
        }
    }

    public function close(): void
    {
        $this->logger->info('[T][CLOSE]');
        if ($this->running) {
            $this->running = false;
            foreach ($this->ircClient->getChannels() as $channel) {
                $this->ircClient->part($channel);
            }
            $this->ircClient->close();
        }
    }

    /**
     * Connect the bot to Twitch
     * This command should not be run while the bot is still connected to Twitch
     * Additional handling may be needed in the case of disconnect via $connection->on('close' (See: Issue #1 on GitHub)
     */
    protected function connect(): void
    {
        if ($this->connection) {
            $this->logger->error('[T][SYMANTICS ERROR] A connection already exists!');

            return;
        }

        $this->ircClient->on(Tmi\Events\Twitch\MessageEvent::class, function (Tmi\Events\Twitch\MessageEvent $e) {
            $message = new Message($e->channel, $e->user, $e->message);

            $this->process($message);
        });

        $this->ircClient->connect();
    }

    protected function process(Message $message): void
    {
        $response = $this->parseMessage($message);
        if ($response === null) {
            return;
        }

        // why this code ?
        // does it ban someone that the bot says bad words ?
        if ($this->badWordsCheck($response->message)) {
            $this->ban($response->channel, $response->fromUser);
        }
        $payload = '@' . $response->fromUser . ', ' . $response->message . "\n";
        $this->ircClient->say($response->channel, $payload);
    }

    protected function badWordsCheck(string $message): bool
    {
        if (empty($this->badWords)) {
            return false;
        }
        $this->logger->debug('[T][BADWORD CHECK] ' . $message);
        foreach ($this->badWords as $badWord) {
            if (str_contains($message, $badWord)) {
                $this->logger->info('[T][BADWORD FOUND] ' . $badWord);

                return true;
            }
        }

        return false;
    }

    protected function parseMessage(Message $message): ?Response
    {
        $this->logger->debug('[PRIVMSG] (#' . $message->channel . ') ' . $message->user . ': ' . $message->text);

        if ($this->badWordsCheck($message->text)) {
            $this->ban($message->channel, $message->user);
        }

        $response = $this->commands->handle($message);

        if (!$response) {
            return null;
        }

        return new Response($message->channel, $message->user, $response);
    }

    public function ban(string $channel, string $user, string $reason = ''): void
    {
        $this->ircClient->say($channel, '/ban ' . $user . ' ' . $reason);
    }
}
