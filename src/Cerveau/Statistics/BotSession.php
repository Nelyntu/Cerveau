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
    public float $durationInMinutes;
    public float $totalWatchTime;

    /**
     * @param ChatEvent[] $chatEvents
     */
    public function __construct(public readonly \DateTimeImmutable $start,
                                public readonly \DateTimeImmutable $end,
                                public readonly array              $chatEvents,
    )
    {
        $chatters = array_map(fn(ChatEvent $chatEvent) => $chatEvent->getUser()->getLogin(), $this->chatEvents);
        $this->chatters = array_unique($chatters);
        $this->durationInMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60.0;
    }

    /**
     * @param array<string, float> $watchTimes
     */
    public function setWatchTimes(array $watchTimes): void
    {
        $this->watchTimes = $watchTimes;

        $this->totalWatchTime = array_reduce($watchTimes, fn($carry, float $timeWatch) => $carry + $timeWatch, 0.0);

        $this->avgView = $this->durationInMinutes === 0.0 ? 0.0 : $this->totalWatchTime / $this->durationInMinutes;
    }
}
