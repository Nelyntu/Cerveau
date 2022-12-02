<?php

namespace Cerveau\Twitch;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TwitchApi\TwitchApi;

class UserApi
{
    public function __construct(
        protected readonly TwitchApi         $twitchApi,
        protected readonly FilesystemAdapter $cache,
    )
    {
    }

    /**
     * @throws UserNotFound
     */
    public function getUserByName(string $name): User
    {
        $accessToken = $this->getAccessToken('');
        $response = $this->twitchApi->getUsersApi()->getUserByUsername($accessToken, $name);

        /** @var array{data: array<array{id: int, login: string, display_name: string, display_name: string, created_at: string}>} $decodedResponse */
        $decodedResponse = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (empty($decodedResponse['data'])) {
            throw new UserNotFound($name);
        }

        $data = $decodedResponse['data'][0];

        /** @var \DateTimeImmutable $since */
        $since = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sp', $data['created_at']);
        return new User($data['id'], $data['login'], $data['display_name'], $data['display_name'], $since);
    }

    private function getAccessToken(string $twitchScopes): string
    {
        $accessTokenItemCacheKey = 'cerveau:twitch:accesstoken:' . $twitchScopes;
        $accessTokenItemCache = $this->cache->getItem($accessTokenItemCacheKey);
        if ($accessTokenItemCache->isHit()) {
            /** @var string $token */
            $token = $accessTokenItemCache->get();
            return $token;
        }

        $oauth = $this->twitchApi->getOauthApi();

        $token = $oauth->getAppAccessToken($twitchScopes);
        /** @var array{access_token: string, expires_in: int} $data */
        $data = json_decode($token->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $accessToken = $data['access_token'];
        $expiresIn = $data['expires_in'];

        $accessTokenItemCache->expiresAfter($expiresIn - 30);
        $accessTokenItemCache->set($accessToken);
        $this->cache->save($accessTokenItemCache);

        return $accessToken;
    }
}
