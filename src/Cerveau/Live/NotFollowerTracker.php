<?php

namespace Cerveau\Live;

use Cerveau\Live\Event\NotFollowerJoinedEvent;
use Cerveau\Live\Event\NotFollowerLeftEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use Cerveau\Twitch\Follower;
use Cerveau\Twitch\Twitch;
use Evenement\EventEmitter;
use GhostZero\Tmi;
use GhostZero\Tmi\Events\Irc\JoinEvent;
use GhostZero\Tmi\Events\Irc\PartEvent;
use function in_array;

class NotFollowerTracker
{
    final const LIVE_DASHBOARD_NOT_FOLLOWER_JOINED = 'live_dashboard.not_follower_joined';
    final const LIVE_DASHBOARD_NOT_FOLLOWER_LEFT = 'live_dashboard.not_follower_left';

    private readonly EventEmitter $emitter;
    /** @var string[] */
    private array $followers;

    public function __construct(
        private readonly Channel             $channel,
        private readonly Tmi\Client          $liveDashboardClientIrc,
        private readonly UserRepository      $userRepository,
        private readonly Twitch              $twitch,
        private readonly ChatEventRepository $chatEventRepository,
    )
    {
        $this->emitter = new EventEmitter();
    }

    public function getEmitter(): EventEmitter
    {
        return $this->emitter;
    }

    public function startTracking(string $channel): void
    {
        $channelUser = $this->userRepository->getOrCreateByUsername($channel);
        $this->followers = array_map(fn(Follower $follower) => $follower->login, $this->twitch->getFollowers($channelUser));

        $chatters = $this->channel->getRealChatters($channel);
        foreach ($chatters as $username) {
            if (in_array($username, $this->followers, true)) {
                continue;
            }

            if ($channel === $username) {
                continue;
            }

            $chatEvent = $this->chatEventRepository->findLatestEventByUsernameAndChannel($username, $channel);

            $this->emitter->emit(self::LIVE_DASHBOARD_NOT_FOLLOWER_JOINED,
                [$channel, new NotFollowerJoinedEvent($username, $chatEvent?->getCreatedAt())]);
        }

        $this->liveDashboardClientIrc->on(JoinEvent::class, function (JoinEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                return;
            }

            if ($channel === $username) {
                return;
            }

            if (in_array($username, $this->followers, true)) {
                return;
            }

            $chatEvent = $this->chatEventRepository->findLatestEventByUsernameAndChannel($username, $channel);

            $this->emitter->emit(self::LIVE_DASHBOARD_NOT_FOLLOWER_JOINED,
                [$channel, new NotFollowerJoinedEvent($username, $chatEvent?->getCreatedAt())]);
        });

        $this->liveDashboardClientIrc->on(PartEvent::class, function (PartEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                return;
            }

            if (in_array($username, $this->followers, true)) {
                return;
            }

            if ($channel === $username) {
                return;
            }

            $chatEvent = $this->chatEventRepository->findLatestEventByUsernameAndChannel($username, $channel);

            $this->emitter->emit(self::LIVE_DASHBOARD_NOT_FOLLOWER_LEFT,
                [$channel, new NotFollowerLeftEvent($username, $chatEvent?->getCreatedAt())]);
        });
    }
}
