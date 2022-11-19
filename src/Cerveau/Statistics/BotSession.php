<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;

class BotSession
{
    /** @var ChatEvent[] */
    public array $chatEvents;
    public ?\DateTimeImmutable $end = null;

    public function __construct(public readonly \DateTimeImmutable $start)
    {
    }

    public function setEnd(\DateTimeImmutable $end): void
    {
        $this->end = $end;
    }

    public function addChatEvent(ChatEvent $chatEvent): void
    {
        $this->chatEvents[] = $chatEvent;
    }
}
