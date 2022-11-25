<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;

class BotSessionBuilder
{
    /**
     * @param ChatEvent[] $chatEvents
     */
    public function fromChatEvents(array $chatEvents): BotSession
    {
        if (empty($chatEvents)) {
            throw new \DomainException('$chatEvents should have at least one ChatEvent object');
        }

        $start = $chatEvents[0]->getCreatedAt();
        $end = end($chatEvents)->getCreatedAt();

        $session = new BotSession($start, $end, $chatEvents);
        $session->setWatchTimes($this->calculateBotSessionWatchTime($session));

        return $session;
    }

    /**
     * @return array<string, float>
     */
    private function calculateBotSessionWatchTime(BotSession $session): array
    {
        $watchTimes = [];
        foreach ($session->chatters as $chatter) {
            $watchTimes[$chatter] = $this->calculateChatterWatchTime($session, $chatter);
        }

        return $watchTimes;
    }

    private function calculateChatterWatchTime(BotSession $session, string $userName): float
    {
        $chatEvents = array_filter($session->chatEvents, fn(ChatEvent $chatEvent) => $chatEvent->getUsername() === $userName);

        /** @var ?ChatEvent $startChatEventPresence */
        $startChatEventPresence = null;
        $presences = [];
        foreach ($chatEvents as $chatEvent) {
            switch ($chatEvent->getType()) {
                case 'part':
                    if ($startChatEventPresence !== null) {
                        $presences[] = $chatEvent->getCreatedAt()->getTimestamp() - $startChatEventPresence->getCreatedAt()->getTimestamp();
                        $startChatEventPresence = null;
                    }
                    break;
                case 'init':
                case 'message':
                case 'join':
                    if ($startChatEventPresence === null) {
                        $startChatEventPresence = $chatEvent;
                    }
                    break;
            }
        }

        if ($startChatEventPresence !== null) {
            $presences[] = $session->end->getTimestamp() - $startChatEventPresence->getCreatedAt()->getTimestamp();
        }

        $totalPresence = 0;
        foreach ($presences as $time) {
            $totalPresence += $time;
        }

        return $totalPresence / 60;
    }
}
