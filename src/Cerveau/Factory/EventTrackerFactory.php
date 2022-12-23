<?php

namespace Cerveau\Factory;

use Cerveau\EventTracker\EventTracker;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use GhostZero\Tmi;

class EventTrackerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly Tmi\Client          $tmiClient,
                                private readonly ChatEventRepository $chatEventRepository,
                                private readonly UserRepository      $userRepository,
    )
    {
    }

    public function create(): EventTracker
    {
        return new EventTracker($this->channel, $this->tmiClient, $this->chatEventRepository, $this->userRepository);
    }
}
