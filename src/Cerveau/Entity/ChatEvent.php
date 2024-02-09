<?php

namespace Cerveau\Entity;

use Cerveau\Twitch\Channel;

class ChatEvent
{
    protected int $id;
    protected string $channel;

    public static function createStart(
        string             $channel,
        \DateTimeImmutable $createdAt,
        User               $user,
        ?int               $streamId,
    ): self
    {
        return new self($channel, $createdAt, 'start', $user, ['stream_id' => $streamId]);
    }

    public static function createJoin(
        string             $channel,
        \DateTimeImmutable $createdAt,
        User               $user,
    ): self
    {
        return new self($channel, $createdAt, 'join', $user);
    }

    public static function createPart(
        string             $channel,
        \DateTimeImmutable $createdAt,
        User               $user,
    ): self
    {
        return new self($channel, $createdAt, 'part', $user);
    }

    public static function createInit(
        string             $channel,
        \DateTimeImmutable $createdAt,
        User               $user,
    ): self
    {
        return new self($channel, $createdAt, 'init', $user);
    }

    public static function createMessage(
        string             $channel,
        \DateTimeImmutable $createdAt,
        User               $user,
    ): self
    {
        return new self($channel, $createdAt, 'message', $user);
    }

    /**
     * @param array<mixed> $data
     */
    private function __construct(
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
