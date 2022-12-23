<?php

namespace Cerveau\Live\Event;

class ChattersCountUpdatedEvent
{
    public function __construct(public readonly int $chatterCount, public readonly int $botCount)
    {
    }
}
