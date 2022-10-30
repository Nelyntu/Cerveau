<?php

namespace Cerveau\Factory;

use Cerveau\Repository\ChatEventRepository;
use Cerveau\Statistics\Channel;
use Cerveau\Statistics\EventTracker;
use Cerveau\ThirdPartyApis\TwitchInsights;
use GhostZero\Tmi;

class EventTrackerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly TwitchInsights      $twitchInsights,
                                private readonly Tmi\Client          $tmiClient,
                                private readonly ChatEventRepository $chatEventRepository,
    )
    {
    }

    public function create(): EventTracker
    {
        return new EventTracker($this->channel, $this->twitchInsights, $this->tmiClient, $this->chatEventRepository);
    }
}
