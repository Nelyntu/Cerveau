<?php

namespace Cerveau\Twitch;

class Stream
{
    public function __construct(public readonly \Cerveau\Entity\User $user, public readonly ?int $id,)
    {
    }
}
