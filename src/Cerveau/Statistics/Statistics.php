<?php

namespace Cerveau\Statistics;

class Statistics
{
    /**
     * @param BotSession[] $sessions
     */
    public function __construct(public readonly int   $chattersCount,
                                public readonly int   $followersCount,
                                public readonly float $avgWatchTime,
                                public readonly array $sessions)
    {
    }
}
