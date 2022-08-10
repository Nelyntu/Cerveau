<?php

namespace Twitch;

class UserList
{
    public string $streamer;
    /** @var string[] */
    public array $restrictedUsers;

    /**
     * @param string[]  $restrictedUsers
     */
    public function __construct(string $streamer, array $restrictedUsers)
    {
        $this->streamer = $streamer;
        $this->restrictedUsers = $restrictedUsers;
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return array_merge([$this->streamer], $this->restrictedUsers);
    }
}
