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
}
