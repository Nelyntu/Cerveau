<?php

namespace Cerveau\Factory;

use Cerveau\EventTracker\EventTracker;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use Cerveau\Twitch\Twitch;
use GhostZero\Tmi;

class EventTrackerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly Tmi\Client          $liveDashboardClientIrc,
                                private readonly ChatEventRepository $chatEventRepository,
                                private readonly UserRepository      $userRepository,
                                private readonly Twitch              $twitch,
    )
    {
    }

    public function create(): EventTracker
    {
        return new EventTracker($this->channel, $this->liveDashboardClientIrc, $this->chatEventRepository, $this->userRepository, $this->twitch);
    }
}
