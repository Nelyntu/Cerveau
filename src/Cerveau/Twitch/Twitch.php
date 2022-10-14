<?php

namespace Cerveau\Twitch;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TwitchApi\TwitchApi;

class Twitch
{
    public function __construct(protected readonly TwitchApi $twitchApi, protected readonly FilesystemAdapter $cache)
    {
    }

    public function getUserByName(string $name): User
    {
        $accessToken = $this->getAccessToken('');
        $response = $this->twitchApi->getUsersApi()->getUserByUsername($accessToken, $name);

        $decodedResponse = json_decode((string)$response->getBody(), true);
        $data = $decodedResponse['data'][0];

        return new User($data['id'], $data['login'], $data['display_name'], $data['broadcaster_type'], \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sp', $data['created_at']));
    }

    /**
     * @return array<Follower>
     */
    public function getFollowers(User $user): array
    {
        $accessToken = $this->getAccessToken('');

        $cursor = null;
        $followers = [];

        do {
            $response = $this->twitchApi->getUsersApi()
                ->getUsersFollows($accessToken, null, $user->id, 100, $cursor);
            $decodedResponse = json_decode((string)$response->getBody(), true);

            foreach ($decodedResponse['data'] as $relation) {
                $followers[] = new Follower($relation['from_id'], $relation['from_login'], $relation['from_name']);
            }

            if (array_key_exists('cursor', $decodedResponse['pagination'])) {
                $cursor = $decodedResponse['pagination']['cursor'];
            } else {
                $cursor = null;
            };
            $goNextPage = $cursor !== null;
        } while ($goNextPage);

        return $followers;
    }

    /**
     * @return string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getChatters(string $channel): array
    {
        $httpClient = new \GuzzleHttp\Client(['base_uri' => 'https://tmi.twitch.tv']);
        $response = $httpClient->request('GET', sprintf("/group/user/%s/chatters", $channel));
        /** @var array<string, array<string, string[]>> $chattersResult */
        $chattersResult = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $chattersByType = $chattersResult['chatters'];

        return array_reduce($chattersByType, static fn($carry, $typedChatters) => array_merge($carry, $typedChatters), []);
    }

    private function getAccessToken(string $twitchScopes): string
    {
        $accessTokenItemCacheKey = 'cerveau:twitch:accesstoken:' . $twitchScopes;
        $accessTokenItemCache = $this->cache->getItem($accessTokenItemCacheKey);
        if ($accessTokenItemCache->isHit()) {
            return $accessTokenItemCache->get();
        }

        $oauth = $this->twitchApi->getOauthApi();

        $token = $oauth->getAppAccessToken($twitchScopes);
        $data = json_decode($token->getBody()->getContents());

        $accessToken = $data->access_token;
        $expiresIn = $data->expires_in;

        $accessTokenItemCache->expiresAfter($expiresIn - 30);
        $accessTokenItemCache->set($accessToken);
        $this->cache->save($accessTokenItemCache);

        return $accessToken;
    }
}
