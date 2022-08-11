<?php

namespace Twitch;

class UserList
{
    /**
     * @param string[]  $restrictedUsers
     */
    public function __construct(public string $streamer, public array $restrictedUsers)
    {
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return array_merge([$this->streamer], $this->restrictedUsers);
    }
}
