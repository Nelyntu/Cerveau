<?php

namespace Cerveau\Entity;

use Cerveau\Twitch\Channel;

class ChatEvent
{
    protected int $id;
    protected string $channel;

    /**
     * @param array<mixed> $data
     */
    public function __construct(
        string                       $channel,
        protected \DateTimeImmutable $createdAt,
        protected string             $type,
        protected User               $user,
        protected array              $data = [],
    )
    {
        $this->channel = Channel::sanitize($channel);
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
