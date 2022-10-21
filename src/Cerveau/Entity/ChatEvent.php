<?php

namespace Cerveau\Entity;

use Cerveau\Statistics\Channel;

class ChatEvent
{
    protected int $id;
    protected string $channel;

    public function __construct(
        protected string             $username,
        string                       $channel,
        protected \DateTimeImmutable $createdAt,
        protected string             $type,
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
}
