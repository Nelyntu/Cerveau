<?php

namespace Twitch;

use GhostZero\Tmi;
use Psr\Log\LoggerInterface;

class Twitch
{
    /** @var string[] */
    private array $badWords = [];
    protected bool $running = false;

    /**
     * @param mixed[] $options
     */
    public function __construct(
        private readonly Tmi\Client $ircClient,
        private readonly LoggerInterface $logger,
        array $options,
        protected CommandDispatcher $commands
    ) {
        if (PHP_SAPI !== 'cli') {
            trigger_error(
                'Cerveau will not run on a webserver. Please use PHP CLI to run a Cerveau self-bot.',
                E_USER_ERROR);
        }

        if (is_array($options['badwords'])) {
            $this->badWords = $options['badwords'];
        }
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
     */
    protected function connect(): void
    {
        $this->ircClient->on(Tmi\Events\Twitch\MessageEvent::class, function (Tmi\Events\Twitch\MessageEvent $e) {
            $message = new Message($e->channel, $e->user, $e->message);

            $this->process($message);
        });

        $this->ircClient->connect();
    }

    protected function process(Message $message): void
    {
        $response = $this->parseMessage($message);
        if (!$response instanceof \Twitch\Response) {
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
