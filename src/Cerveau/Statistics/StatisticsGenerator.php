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
        $sessions = [];
        $session = null;
        $prevEventDate = null;
        foreach ($chatEvents as $chatEvent) {
            if ($session === null) {
                $session = new BotSession($chatEvent->getCreatedAt());
                $sessions[] = $session;
            } else {
                /** @phpstan-ignore-next-line */
                $intervalInSeconds = $chatEvent->getCreatedAt()->getTimestamp() - $prevEventDate->getTimestamp();
                if ($intervalInSeconds > 60 * 15) {
                    /** @phpstan-ignore-next-line */
                    $session->setEnd($prevEventDate);
                    $session->addChatEvent($chatEvent);
                    $session = null;
                    continue;
                }
            }
            $prevEventDate = $chatEvent->getCreatedAt();
            $session->addChatEvent($chatEvent);
        }

        if ($prevEventDate !== null) {
            /** @phpstan-ignore-next-line */
            $session->setEnd($prevEventDate);
        }

        return $sessions;
    }
}
