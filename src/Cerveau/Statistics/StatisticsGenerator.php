<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Twitch\Follower;
use Cerveau\Twitch\Twitch;

class StatisticsGenerator
{
    public function __construct(private readonly ChatEventRepository $chatEventRepository,
                                private readonly Twitch $twitch,
                                private readonly BotSessionBuilder $botSessionBuilder,
    )
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
        $chatEventsChunks = [];
        $currentChatEventsChunk = [];
        foreach ($chatEvents as $chatEvent) {
            if (!empty($currentChatEventsChunk) && $chatEvent->getType() === 'start') {
                $chatEventsChunks[] = $currentChatEventsChunk;
                $currentChatEventsChunk = [];
                continue;
            }

            $currentChatEventsChunk[] = $chatEvent;
        }

        if (!empty($currentChatEventsChunk)) {
            $chatEventsChunks[] = $currentChatEventsChunk;
        }

        /** @var BotSession[] $sessions */
        $sessions = [];
        foreach ($chatEventsChunks as $chatEvents) {
            $sessions[] = $this->botSessionBuilder->fromChatEvents($chatEvents);
        }

        return $sessions;
    }
}
