<?php

namespace Cerveau\EventTracker;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use Cerveau\Twitch\Channel;
use Cerveau\Twitch\Twitch;
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
        private readonly Twitch $twitch,
    )
    {
    }

    public function startTracking(string $channel): void
    {
        $user = $this->userRepository->getOrCreateByUsername($channel);
        $stream = $this->twitch->getStream($user);

        $chatEvent = ChatEvent::createStart($channel, new DateTimeImmutable(), $user, $stream->id);

        $this->chatEventRepository->add($chatEvent);

        $chatters = $this->channel->getRealChatters($channel);
        foreach ($chatters as $chatter) {
            $user = $this->userRepository->getOrCreateByUsername($chatter);
            $chatEvent = ChatEvent::createInit($channel, new DateTimeImmutable(), $user);

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
            $chatEvent = ChatEvent::createJoin($event->channel, new DateTimeImmutable(), $user);

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
            $chatEvent = ChatEvent::createPart($event->channel, new DateTimeImmutable(), $user);

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
            $chatEvent = ChatEvent::createMessage($event->channel, new DateTimeImmutable(), $user);

            $this->chatEventRepository->add($chatEvent);
        });
    }
}
