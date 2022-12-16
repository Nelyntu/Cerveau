<?php

namespace Cerveau\Statistics;

class LiveNotFollowerJoinedEvent
{
    public function __construct(public readonly string $username, public readonly ?\DateTimeImmutable $lastSeen)
    {
    }
}
