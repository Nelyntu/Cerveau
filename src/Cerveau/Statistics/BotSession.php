<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;

class BotSession
{
    /** @var ChatEvent[] */
    public array $chatEvents;
    public ?\DateTimeImmutable $end = null;
    /** @var array<string, float> */
    public array $watchTimes;
    /**
     * @var string[]
     */
    public array $chatters;
    public float $avgView;

    public function __construct(public readonly \DateTimeImmutable $start)
    {
    }

    public function setEnd(\DateTimeImmutable $end): void
    {
        $this->end = $end;
    }

    public function addChatEvent(ChatEvent $chatEvent): void
    {
        $this->chatEvents[] = $chatEvent;
    }

    /**
     * @param array<string, float> $watchTimes
     */
    public function setWatchTimes(array $watchTimes): void
    {
        $this->watchTimes = $watchTimes;

        $totalWatchTime = array_reduce($watchTimes,fn($carry, float $timeWatch) => $carry + $timeWatch, 0.0);

        /** @phpstan-ignore-next-line */
        $durationInMinutes = ($this->end->getTimestamp() - $this->start->getTimestamp()) / 60;

        $this->avgView = $totalWatchTime / $durationInMinutes;
    }

    /**
     * @param string[] $chatters
     */
    public function setChatters(array $chatters): void
    {
        $this->chatters = $chatters;
    }
}
