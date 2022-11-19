<?php

namespace Cerveau\Repository;

use Cerveau\Entity\ChatEvent;
use Doctrine\ORM\EntityManagerInterface;

class ChatEventRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function add(ChatEvent $chatEvent): void
    {
        $this->entityManager->persist($chatEvent);
        $this->entityManager->flush();
    }

    /**
     * @return ChatEvent[]
     */
    public function getBetweenDates(string $channel, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        /** @var ChatEvent[] $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('chat_event')
            ->from(ChatEvent::class, 'chat_event')
            ->where('chat_event.channel = :channel')
            ->andWhere('chat_event.createdAt >= :start')
            ->andWhere('chat_event.createdAt <= :end')
            ->orderBy('chat_event.id')
            ->setParameter('channel', $channel)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
        return $result;
    }
}
