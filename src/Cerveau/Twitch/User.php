<?php

namespace Cerveau\Twitch;

class User
{
    public function __construct(public readonly int                $id,
                                public readonly string             $login,
                                public readonly string             $name,
                                public readonly string             $broadcasterType,
                                public readonly \DateTimeImmutable $since)
    {
    }
}
