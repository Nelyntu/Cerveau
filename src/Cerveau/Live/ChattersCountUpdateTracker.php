<?php

namespace Cerveau\Live;

use Cerveau\Live\Event\ChattersCountUpdatedEvent;
use Cerveau\Twitch\Channel;
use Evenement\EventEmitter;
use GhostZero\Tmi;
use GhostZero\Tmi\Events\Irc\JoinEvent;
use GhostZero\Tmi\Events\Irc\PartEvent;
use GhostZero\Tmi\Events\Twitch\MessageEvent;
use function in_array;

class ChattersCountUpdateTracker
{
    /** @var string[] */
    private array $chatters;
    /** @var string[] */
    private array $bots;
    private readonly EventEmitter $emitter;

    public function __construct(
        private readonly Channel    $channel,
        private readonly Tmi\Client $tmiClient,
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
        $this->chatters = $this->channel->getRealChatters($channel);
        $this->bots = $this->channel->getBots($channel);

        $this->getChattersUpdate($channel);

        $this->tmiClient->on(JoinEvent::class, function (JoinEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                if (in_array($username, $this->bots, true)) {
                    return;
                }

                $this->bots[] = $username;

                $this->getChattersUpdate($channel);

                return;
            }

            if (in_array($username, $this->chatters, true)) {
                return;
            }

            $this->chatters[] = $username;

            $this->getChattersUpdate($channel);
        });

        $this->tmiClient->on(PartEvent::class, function (PartEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            if ($this->channel->isBot($event->user)) {
                $userKey = array_search($event->user, $this->bots, true);
                if ($userKey === false) {
                    return;
                }

                unset($this->bots[$userKey]);

                $this->getChattersUpdate($channel);
            }

            $userKey = array_search($event->user, $this->chatters, true);
            if ($userKey === false) {
                return;
            }

            unset($this->chatters[$userKey]);

            $this->getChattersUpdate($channel);
        });

        $this->tmiClient->on(MessageEvent::class, function (MessageEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->channel->isBot($username)) {
                if (in_array($username, $this->bots, true)) {
                    return;
                }

                $this->bots[] = $username;

                $this->getChattersUpdate($channel);

                return;
            }

            if (in_array($username, $this->chatters, true)) {
                return;
            }

            $this->chatters[] = $username;

            $this->getChattersUpdate($channel);
        });
    }

    public function getChattersUpdate(string $channel): void
    {
        $this->emitter->emit('live_channel_viewers.chatters_updated',
            [$channel, new ChattersCountUpdatedEvent(count($this->chatters), count($this->bots))]);
    }
}
