<?php

namespace Cerveau\Bot;

use Cerveau\Twitch\Channel;
use GhostZero\Tmi;
use Psr\Log\LoggerInterface;

class Bot
{
    /** @var string[] */
    private array $badWords = [];
    protected bool $running = false;

    /**
     * @param mixed[] $options
     * @param string[] $channels
     */
    public function __construct(
        private readonly Tmi\Client      $botClientIrc,
        private readonly LoggerInterface $logger,
        array                            $options,
        protected CommandDispatcher      $commands,
        private readonly array           $channels,
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
            foreach ($this->botClientIrc->getChannels() as $channel) {
                $this->botClientIrc->part($channel);
            }
            $this->botClientIrc->close();
        }
    }

    /**
     * Connect the bot to Twitch
     */
    protected function connect(): void
    {
        $this->botClientIrc->on(Tmi\Events\Twitch\MessageEvent::class, function (Tmi\Events\Twitch\MessageEvent $e) {
            if (!\in_array(Channel::sanitize($e->channel), $this->channels)) {
                return;
            }
            $message = new Message($e->channel, $e->user, $e->message);

            $this->process($message);
        });

        $this->botClientIrc->connect();
    }

    protected function process(Message $message): void
    {
        $response = $this->parseMessage($message);
        if (!$response instanceof Response) {
            return;
        }

        $payload = '@' . $response->fromUser . ', ' . $response->message . "\n";
        $this->botClientIrc->say($response->channel, $payload);
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
        $this->botClientIrc->say($channel, '/ban ' . $user . ' ' . $reason);
    }
}
