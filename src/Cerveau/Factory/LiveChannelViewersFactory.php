<?php

namespace Cerveau\Factory;

use Cerveau\Statistics\Channel;
use Cerveau\Statistics\LiveChannelViewers;
use GhostZero\Tmi;

class LiveChannelViewersFactory
{
    public function __construct(private readonly Channel    $channel,
                                private readonly Tmi\Client $tmiClient,
    )
    {
    }

    public function create(): LiveChannelViewers
    {
        return new LiveChannelViewers($this->channel, $this->tmiClient,);
    }
}
