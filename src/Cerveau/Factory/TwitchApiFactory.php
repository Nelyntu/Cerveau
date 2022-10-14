<?php

namespace Cerveau\Factory;

use TwitchApi\TwitchApi;

class TwitchApiFactory
{
    public function __construct(private readonly string $twitchClientId, private readonly string $twitchClientSecret)
    {
    }

    public function createAPI(): TwitchApi
    {
        $helixGuzzleClient = new \TwitchApi\HelixGuzzleClient($this->twitchClientId);
        return new \TwitchApi\TwitchApi($helixGuzzleClient, $this->twitchClientId, $this->twitchClientSecret);
    }
}
