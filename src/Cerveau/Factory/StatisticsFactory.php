<?php

namespace Cerveau\Factory;

use Cerveau\Repository\ChatEventRepository;
use Cerveau\Statistics\Channel;
use Cerveau\Statistics\Statistics;
use Cerveau\ThirdPartyApis\TwitchInsights;
use GhostZero\Tmi;

class StatisticsFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly TwitchInsights      $twitchInsights,
                                private readonly Tmi\Client          $tmiClient,
                                private readonly ChatEventRepository $chatEventRepository,
    )
    {
    }

    public function createStatistics(): Statistics
    {
        return new Statistics($this->channel, $this->twitchInsights, $this->tmiClient, $this->chatEventRepository);
    }
}
