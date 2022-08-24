<?php

namespace Cerveau;

class UserList
{
    /**
     * @param string[]  $superUsers
     */
    public function __construct(public string $streamer, public array $superUsers)
    {
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return array_merge([$this->streamer], $this->superUsers);
    }
}
