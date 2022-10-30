<?php

namespace Cerveau;

class UserList
{
    /**
     * @param string[]  $superUsers
     */
    public function __construct(public string $botNickname, public array $superUsers)
    {
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return array_merge([$this->botNickname], $this->superUsers);
    }
}
