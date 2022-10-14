<?php

namespace Cerveau\Factory;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;

class IRCClientFactory
{
    /**
     * @param string[]  $channels
     */
    public function __construct(private readonly string $nick, private readonly array $channels, private readonly string $secret)
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
