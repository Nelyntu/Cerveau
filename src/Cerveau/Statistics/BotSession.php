<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;

class BotSession
{
    /** @var array<string, float> */
    public array $watchTimes;
    /**
     * @var string[]
     */
    public array $chatters;
    public float $avgView;

    /**
     * @param ChatEvent[] $chatEvents
     */
    public function __construct(public readonly \DateTimeImmutable $start,
                                public readonly \DateTimeImmutable $end,
                                public readonly array              $chatEvents,
    )
    {
        $chatters = array_map(fn(ChatEvent $chatEvent) => $chatEvent->getUsername(), $this->chatEvents);
        $this->chatters = array_unique($chatters);
    }

    /**
     * @param array<string, float> $watchTimes
     */
    public function setWatchTimes(array $watchTimes): void
    {
        $this->watchTimes = $watchTimes;

        $totalWatchTime = array_reduce($watchTimes, fn($carry, float $timeWatch) => $carry + $timeWatch, 0.0);

        $durationInMinutes = ($this->end->getTimestamp() - $this->start->getTimestamp()) / 60;

        $this->avgView = $totalWatchTime / $durationInMinutes;
    }
}
