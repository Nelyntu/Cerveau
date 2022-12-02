<?php

namespace Cerveau\Twitch;

class UserNotFound extends \DomainException
{
    private readonly string $username;

    public function __construct(string $username)
    {
        parent::__construct('"' . $username . '" doesn\'t exist');
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
