<?php

namespace Cerveau\Twitch;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TwitchApi\TwitchApi;

class UserApi extends AbstractTwitchApi
{
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
}
