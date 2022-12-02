<?php

namespace Cerveau\Twitch;

use Cerveau\Repository\UserRepository;

class FollowerBuilder
{
    public function __construct(
        readonly private UserRepository $userRepository,
    )
    {
    }

    public function build(int $id, string $login, string $name): Follower
    {
        $follower = new Follower($id, $login, $name);

        $this->userRepository->refreshFrom($follower);

        return new Follower($id, $login, $name);
    }
}
