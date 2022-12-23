<?php

namespace Cerveau\Bot;

use GhostZero\Tmi\Client;

class AutoMessage
{
    /** @var string[] */
    private array $messages = [];

    /**
     * @param int $interval in minutes
     */
    public function __construct(private readonly string $botNickname, private readonly Client $client, private readonly int $interval)
    {
    }

    /**
     * @param string[] $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    public function start(): void
    {
        $this->sendNextMessage();

        $this->client->getLoop()->addPeriodicTimer($this->interval * 60, function () {
            $this->sendNextMessage();
        });
    }

    private function sendNextMessage(): void
    {
        $message = current($this->messages);
        if ($message === false) {
            return;
        }

        $this->client->say($this->botNickname, $message);
        $nextValue = next($this->messages);
        if ($nextValue === false) {
            reset($this->messages);
        }
    }
}
