<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use DateTimeImmutable;
use GhostZero\Tmi;
use GhostZero\Tmi\Events\Irc\JoinEvent;
use GhostZero\Tmi\Events\Irc\PartEvent;
use GhostZero\Tmi\Events\Twitch\MessageEvent;
use function in_array;

class EventTracker
{
    /** @var string[] */
    private array $chatters;
    private ?string $debugOnChannel = null;

    public function __construct(
        private readonly Channel             $channel,
        private readonly Tmi\Client          $tmiClient,
        private readonly ChatEventRepository $chatEventRepository
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

        $chatEvent = new ChatEvent($channel, $channel, new DateTimeImmutable(), 'start');

        $this->chatEventRepository->add($chatEvent);

        foreach ($this->chatters as $chatter) {
            $chatEvent = new ChatEvent($chatter, $channel, new DateTimeImmutable(), 'init');

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

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'join');

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

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'part');

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

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'message');

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
