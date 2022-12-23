<?php

namespace Cerveau\EventTracker;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use DateTimeImmutable;
use GhostZero\Tmi;
use GhostZero\Tmi\Events\Irc\JoinEvent;
use GhostZero\Tmi\Events\Irc\PartEvent;
use GhostZero\Tmi\Events\Twitch\MessageEvent;

class EventTracker
{
    public function __construct(
        private readonly Channel             $channel,
        private readonly Tmi\Client          $liveDashboardClientIrc,
        private readonly ChatEventRepository $chatEventRepository,
        private readonly UserRepository      $userRepository,
    )
    {
    }

    public function startTracking(string $channel): void
    {
        $user = $this->userRepository->getOrCreateByUsername($channel);
        $chatEvent = new ChatEvent($channel, new DateTimeImmutable(), 'start', $user);

        $this->chatEventRepository->add($chatEvent);

        $chatters = $this->channel->getRealChatters($channel);
        foreach ($chatters as $chatter) {
            $user = $this->userRepository->getOrCreateByUsername($chatter);
            $chatEvent = new ChatEvent($channel, new DateTimeImmutable(), 'init', $user);

            $this->chatEventRepository->add($chatEvent);
        }

        $this->liveDashboardClientIrc->on(JoinEvent::class, function (JoinEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            if ($this->channel->isBot($event->user)) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'join', $user);

            $this->chatEventRepository->add($chatEvent);
        });

        $this->liveDashboardClientIrc->on(PartEvent::class, function (PartEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            if ($this->channel->isBot($event->user)) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'part', $user);

            $this->chatEventRepository->add($chatEvent);
        });

        $this->liveDashboardClientIrc->on(MessageEvent::class, function (MessageEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            if ($this->channel->isBot($event->user)) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'message', $user);

            $this->chatEventRepository->add($chatEvent);
        });
    }
}
