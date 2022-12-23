<?php

namespace Cerveau\Live\Event;

class NotFollowerLeftEvent
{
    public function __construct(public readonly string $username, public readonly ?\DateTimeImmutable $lastSeen)
    {
    }
}
