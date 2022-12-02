<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\Repository\UserRepository;
use DateTimeImmutable;
use GhostZero\Tmi;
use GhostZero\Tmi\Events\Irc\JoinEvent;
use GhostZero\Tmi\Events\Irc\PartEvent;
use GhostZero\Tmi\Events\Twitch\MessageEvent;
use function in_array;

class EventTracker
{
    // TODO : track chatters in a separated class like "LiveChannel"
    /** @var string[] */
    private array $chatters;
    private ?string $debugOnChannel = null;

    public function __construct(
        private readonly Channel             $channel,
        private readonly Tmi\Client          $tmiClient,
        private readonly ChatEventRepository $chatEventRepository,
        private readonly UserRepository      $userRepository,
    )
    {
    }

    public function setDebugOnChannel(?string $channel): void
    {
        $this->debugOnChannel = $channel;
    }

    public function startTracking(string $channel): void
    {
        $this->chatters = $this->channel->getRealChatters($channel);

        $user = $this->userRepository->getOrCreateByUsername($channel);
        $chatEvent = new ChatEvent($channel, new DateTimeImmutable(), 'start', $user);

        $this->chatEventRepository->add($chatEvent);

        foreach ($this->chatters as $chatter) {
            $user = $this->userRepository->getOrCreateByUsername($chatter);
            $chatEvent = new ChatEvent($channel, new DateTimeImmutable(), 'init', $user);

            $this->chatEventRepository->add($chatEvent);
        }

        if ($this->debugOnChannel) {
            $this->tmiClient->say($channel, '[DEBUG] First detect chatters: ' . implode(', ', $this->chatters));
        }

        $this->tmiClient->on(JoinEvent::class, function (JoinEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'join', $user);

            $this->chatEventRepository->add($chatEvent);

            if (in_array($username, $this->chatters, true)) {
                return;
            }

            if ($this->debugOnChannel) {
                $this->tmiClient->say($channel, '[DEBUG] New chatter detected: ' . $username);
            }

            $this->chatters[] = $username;
        });

        $this->tmiClient->on(PartEvent::class, function (PartEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'part', $user);

            $this->chatEventRepository->add($chatEvent);

            $userKey = array_search($event->user, $this->chatters, true);
            if ($userKey === false) {
                return;
            }

            if ($this->debugOnChannel) {
                $this->tmiClient->say($channel, '[DEBUG] Chatter leaved: ' . $event->user);
            }

            unset($this->chatters[$userKey]);
        });

        $this->tmiClient->on(MessageEvent::class, function (MessageEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                return;
            }

            $user = $this->userRepository->getOrCreateByUsername($event->user);
            $chatEvent = new ChatEvent($event->channel, new DateTimeImmutable(), 'message', $user);

            $this->chatEventRepository->add($chatEvent);

            if (in_array($username, $this->chatters, true)) {
                return;
            }

            if ($this->debugOnChannel) {
                $this->tmiClient->say($channel, '[DEBUG] New chatter detected: ' . $username);
            }

            $this->chatters[] = $username;
        });
    }
}
