<?php

namespace Cerveau\Live\Factory;

use Cerveau\Live\NotFollowerTracker;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use Cerveau\Twitch\Twitch;
use GhostZero\Tmi;

class NotFollowerTrackerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly Tmi\Client          $liveDashboardClientIrc,
                                private readonly UserRepository      $userRepository,
                                private readonly Twitch              $twitch,
                                private readonly ChatEventRepository $chatEventRepository,
    )
    {
    }

    public function create(): NotFollowerTracker
    {
        return new NotFollowerTracker(
            channel: $this->channel,
            liveDashboardClientIrc: $this->liveDashboardClientIrc,
            userRepository: $this->userRepository,
            twitch: $this->twitch,
            chatEventRepository: $this->chatEventRepository,
        );
    }
}
