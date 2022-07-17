<?php

namespace Twitch;

class UserList
{
    public string $streamer;
    /** @var string[] */
    public array $restrictedUsers;

    public function __construct($streamer, $restrictedUsers)
    {
        $this->streamer = $streamer;
        $this->restrictedUsers = $restrictedUsers;
    }

    public function getAll(): array
    {
        return array_merge([$this->streamer, $this->restrictedUsers]);
    }
}
