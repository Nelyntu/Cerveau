<?php

namespace Cerveau\Twitch;

use Cerveau\ThirdPartyApis\TwitchInsights;

class Channel
{
    public function __construct(protected TwitchInsights $twitchInsights)
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
        $allChatters = $this->getChatters($channel);

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
        $allChatters = $this->getChatters($channel);

        return array_diff($allChatters, $bots);
    }

    public static function sanitize(string $channel): string
    {
        if ($channel[0] === '#') {
            $channel = substr($channel, 1);
        }

        return strtolower($channel);
    }

    public function isBot(string $username): bool
    {
        return $this->twitchInsights->isBot($username);
    }

    /**
     * @return string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    private function getChatters(string $channel): array
    {
        $httpClient = new \GuzzleHttp\Client(['base_uri' => 'https://tmi.twitch.tv']);
        $response = $httpClient->request('GET', sprintf("/group/user/%s/chatters", $channel));
        /** @var array<string, array<string, string[]>> $chattersResult */
        $chattersResult = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $chattersByType = $chattersResult['chatters'];

        return array_reduce($chattersByType, static fn($carry, $typedChatters) => array_merge($carry, $typedChatters), []);
    }
}
