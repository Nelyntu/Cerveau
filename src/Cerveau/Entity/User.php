<?php

namespace Cerveau\Entity;

use Cerveau\Twitch\Follower;

class User
{
    public function __construct(
        readonly protected int $id,
        protected string       $login,
        protected string       $name,
    )
    {
    }

    public static function createFromFollower(Follower $follower): self
    {
        return new self($follower->id, $follower->login, $follower->name);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function update(string $login, string $name): void
    {
        $this->login = $login;
        $this->name = $name;
    }
}
