<?php

namespace Cerveau\Live\Factory;

use Cerveau\Live\ChattersCountUpdateTracker;
use Cerveau\Twitch\Channel;
use GhostZero\Tmi;

class ChattersCountUpdateTrackerFactory
{
    public function __construct(private readonly Channel    $channel,
                                private readonly Tmi\Client $tmiClient,
    )
    {
    }

    public function create(): ChattersCountUpdateTracker
    {
        return new ChattersCountUpdateTracker($this->channel, $this->tmiClient,);
    }
}
