<?php

namespace Cerveau\Statistics;

class LiveNotFollowerLeftEvent
{
    public function __construct(public readonly string $username, public readonly ?\DateTimeImmutable $lastSeen)
    {
    }
}
