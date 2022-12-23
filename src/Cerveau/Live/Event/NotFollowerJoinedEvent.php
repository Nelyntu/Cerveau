<?php

namespace Cerveau\Live\Event;

class NotFollowerJoinedEvent
{
    public function __construct(public readonly string $username, public readonly ?\DateTimeImmutable $lastSeen)
    {
    }
}
