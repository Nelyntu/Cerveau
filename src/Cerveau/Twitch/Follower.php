<?php

namespace Cerveau\Twitch;

class Follower
{
    public function __construct(public readonly int $id, public readonly string $login, public readonly string $name)
    {
    }
}
