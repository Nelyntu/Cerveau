<?php

namespace Cerveau\ThirdPartyApis;

class TwitchInsights
{
    /** @var string[] */
    private array $bots = [];

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @return string[]
     */
    public function getBots(): array
    {
        return $this->bots;
    }

    public function isBot(string $username): bool
    {
        return \in_array($username, $this->bots, true);
    }

    private function initialize(): void
    {
        $httpClient = new \GuzzleHttp\Client(['base_uri' => 'https://api.twitchinsights.net']);
        $response = $httpClient->request('GET', '/v1/bots/all');
        /** @var array{bots: array<array{0: string, 1: int, 2: int}>} $botStatList */
        $botStatList = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $bots = array_map(static fn(array $botStat) => $botStat[0], $botStatList['bots']);
        $this->bots = array_unique($bots);
    }
}
