<?php

namespace Cerveau\Statistics;

use Cerveau\ThirdPartyApis\Twitch;
use Cerveau\ThirdPartyApis\TwitchInsights;

class Channel
{
    public function __construct(protected TwitchInsights $twitchInsights, private readonly Twitch $twitch)
    {
    }

    /**
     * @return string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getBots(string $channel): array
    {
        $bots = $this->twitchInsights->getBots();
        $allChatters = $this->twitch->getChatters($channel);

        return array_intersect($allChatters, $bots);
    }

    /**
     * @return string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getRealChatters(string $channel): array
    {
        $bots = $this->twitchInsights->getBots();
        $allChatters = $this->twitch->getChatters($channel);

        return array_diff($allChatters, $bots);
    }
}
