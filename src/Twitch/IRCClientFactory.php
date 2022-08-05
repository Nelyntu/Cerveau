<?php

namespace Twitch;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;

class IRCClientFactory
{
    public function __construct(private string $nick, private array $channels, private string $secret)
    {
    }

    public function createClient(): Client
    {
        return new Client(new ClientOptions([
            'options' => ['debug' => true],
            'connection' => [
                'secure' => true,
                'reconnect' => true,
                'rejoin' => true,
            ],
            'identity' => [
                'username' => $this->nick,
                'password' => $this->secret,
            ],
            'channels' => $this->channels,
        ]));
    }
}
