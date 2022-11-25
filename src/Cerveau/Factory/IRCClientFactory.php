<?php

namespace Cerveau\Factory;

use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;

class IRCClientFactory
{
    /**
     * @param string[] $channels
     * @param string[] $statsChannels
     */
    public function __construct(private readonly string $botNickname,
                                private readonly array  $channels,
                                private readonly array  $statsChannels,
                                private readonly string $secret,
    )
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
                'username' => $this->botNickname,
                'password' => $this->secret,
            ],
            'channels' => array_unique([...$this->statsChannels, ...$this->channels,]),
        ]));
    }
}
