<?php

namespace Cerveau\Factory;

use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Statistics\Channel;
use Cerveau\Statistics\LiveNotFollower;
use Cerveau\Twitch\Twitch;
use GhostZero\Tmi;

class LiveNotFollowerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly Tmi\Client          $tmiClient,
                                private readonly UserRepository      $userRepository,
                                private readonly Twitch              $twitch,
                                private readonly ChatEventRepository $chatEventRepository,
    )
    {
    }

    public function create(): LiveNotFollower
    {
        return new LiveNotFollower(
            channel: $this->channel,
            tmiClient: $this->tmiClient,
            userRepository: $this->userRepository,
            twitch: $this->twitch,
            chatEventRepository: $this->chatEventRepository,
        );
    }
}
