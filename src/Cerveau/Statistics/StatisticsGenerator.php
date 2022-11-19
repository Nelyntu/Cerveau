<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Twitch\Follower;
use Cerveau\Twitch\Twitch;

class StatisticsGenerator
{
    public function __construct(private readonly ChatEventRepository $chatEventRepository, private readonly Twitch $twitch)
    {
    }

    public function generate(string $channel, \DateTimeImmutable $start, \DateTimeImmutable $end): Statistics
    {
        $followers = $this->twitch->getFollowers($this->twitch->getUserByName($channel));
        $followerUsernames = array_map(fn(Follower $follower) => $follower->login, $followers);

        $chatEvents = $this->chatEventRepository->getBetweenDates($channel, $start, $end);

        $chatters = [];

        foreach ($chatEvents as $chatEvent) {
            $chatters[$chatEvent->getUsername()] = $chatEvent->getUsername();
        }

        $chattersCount = count($chatters);
        $presentFollowers = array_reduce(
            $chatters,
            fn(int $carry, string $username) => $carry + (in_array($username, $followerUsernames) ? 1 : 0),
            0);

        $sessions = $this->guessLives($chatEvents);

        return new Statistics($chattersCount, $presentFollowers, 0.0, $sessions);
    }

    /**
     * @param ChatEvent[] $chatEvents
     * @return BotSession[]
     */
    private function guessLives(array $chatEvents): array
    {
        /** @var BotSession[] $sessions */
        $sessions = [];
        $session = null;
        $prevEventDate = null;
        foreach ($chatEvents as $chatEvent) {
            if ($session === null) {
                $session = new BotSession($chatEvent->getCreatedAt());
                $sessions[] = $session;
            } elseif ($chatEvent->getType() === 'start') {
                /** @phpstan-ignore-next-line */
                $session->setEnd($prevEventDate);
                $session->addChatEvent($chatEvent);
                $session = null;
                continue;
            }
            $prevEventDate = $chatEvent->getCreatedAt();
            $session->addChatEvent($chatEvent);
        }

        if ($prevEventDate !== null) {
            /** @phpstan-ignore-next-line */
            $session->setEnd($prevEventDate);
        }

        foreach ($sessions as $session) {
            $session->setWatchTimes($this->calculateBotSessionWatchTime($session));
        }

        return $sessions;
    }

    /**
     * @return array<string, float>
     */
    private function calculateBotSessionWatchTime(BotSession $session): array
    {
        $chatters = array_map(fn(ChatEvent $chatEvent) => $chatEvent->getUsername(), $session->chatEvents);
        $chatters = array_unique($chatters);

        $watchTimes = [];
        foreach($chatters as $chatter) {
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
            /** @phpstan-ignore-next-line */
            $presences[] = $session->end->getTimestamp() - $startChatEventPresence->getCreatedAt()->getTimestamp();
        }

        $totalPresence = 0;
        foreach ($presences as $time) {
            $totalPresence += $time;
        }

        return $totalPresence / 60;
    }
}
