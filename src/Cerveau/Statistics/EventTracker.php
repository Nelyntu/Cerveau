<?php

namespace Cerveau\Statistics;

use Cerveau\Entity\ChatEvent;
use Cerveau\Repository\ChatEventRepository;
use Cerveau\ThirdPartyApis\TwitchInsights;
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

    public function __construct(
        private readonly Channel             $channel,
        private readonly TwitchInsights      $twitchInsights,
        private readonly Tmi\Client          $tmiClient,
        private readonly ChatEventRepository $chatEventRepository
    )
    {
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

//        $this->tmiClient->say(
//            $this->streamer, 'Le bot dit bonjour à : ' . implode(', ', $this->chatters)
//        );

        $this->tmiClient->on(JoinEvent::class, function (JoinEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->twitchInsights->isBot($username)) {
                return;
            }

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'join');

            $this->chatEventRepository->add($chatEvent);

            if (in_array($username, $this->chatters, true)) {
                return;
            }

//            $this->tmiClient->say($this->streamer, 'Coucou ' . $username . ' !');

            $this->chatters[] = $username;
        });

        $this->tmiClient->on(PartEvent::class, function (PartEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }
            $userKey = array_search($event->user, $this->chatters, true);
            if ($userKey === false) {
                return;
            }

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'part');

            $this->chatEventRepository->add($chatEvent);

//            $this->tmiClient->say($event->channel, 'Au revoir ' . $event->user . '!');

            unset($this->chatters[$userKey]);
        });

        $this->tmiClient->on(MessageEvent::class, function (MessageEvent $event) use ($channel) {
            if (Channel::sanitize($event->channel->getName()) !== $channel) {
                return;
            }

            $username = $event->user;

            if ($this->twitchInsights->isBot($username)) {
                return;
            }

            $chatEvent = new ChatEvent($event->user, $event->channel, new DateTimeImmutable(), 'message');

            $this->chatEventRepository->add($chatEvent);

            if (in_array($username, $this->chatters, true)) {
                return;
            }

//            $this->tmiClient->say($this->streamer, 'Coucou ' . $username . ' !');

            $this->chatters[] = $username;
        });
    }
}
