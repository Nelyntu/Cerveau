<?php

namespace Cerveau\Factory;

use Cerveau\Repository\ChatEventRepository;
use Cerveau\Statistics\Channel;
use Cerveau\Statistics\EventTracker;
use GhostZero\Tmi;

class EventTrackerFactory
{
    public function __construct(private readonly Channel             $channel,
                                private readonly Tmi\Client          $tmiClient,
                                private readonly ChatEventRepository $chatEventRepository,
    )
    {
    }

    public function create(): EventTracker
    {
        return new EventTracker($this->channel, $this->tmiClient, $this->chatEventRepository);
    }
}
