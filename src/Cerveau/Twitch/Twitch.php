<?php

namespace Cerveau\Twitch;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TwitchApi\TwitchApi;

class Twitch extends AbstractTwitchApi
{
    public function __construct(TwitchApi                          $twitchApi,
                                FilesystemAdapter                  $cache,
                                protected readonly FollowerBuilder $followerBuilder,
    )
    {
        parent::__construct($twitchApi, $cache);
    }

    /**
     * @return array<Follower>
     */
    public function getFollowers(\Cerveau\Entity\User $user): array
    {
        $accessToken = $this->getAccessToken('');

        $cursor = null;
        $followers = [];

        do {
            $response = $this->twitchApi->getUsersApi()
                ->getUsersFollows($accessToken, null, (string)$user->getId(), 100, $cursor);
            /** @var array{data: array<array{from_id: int, from_login: string, from_name: string}>, pagination: array{cursor?: string}} $decodedResponse */
            $decodedResponse = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($decodedResponse['data'] as $relation) {
                $followers[] = $this->followerBuilder->build($relation['from_id'], $relation['from_login'], $relation['from_name']);
            }

            $cursor = $decodedResponse['pagination']['cursor'] ?? null;
            $goNextPage = $cursor !== null;
        } while ($goNextPage);

        return $followers;
    }
}
