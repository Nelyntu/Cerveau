<?php

namespace Cerveau\Twitch;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TwitchApi\TwitchApi;

class AbstractTwitchApi
{
    public function __construct(
        protected readonly TwitchApi         $twitchApi,
        protected readonly FilesystemAdapter $cache,
    )
    {
    }

    protected function getAccessToken(string $twitchScopes): string
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