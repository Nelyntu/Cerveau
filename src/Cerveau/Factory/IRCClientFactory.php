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

    public function createClientForBot(): Client
    {
        return $this->createClient(true, $this->channels);
    }

    public function createClientForLiveDashboard(): Client
    {
        return $this->createClient(false, $this->statsChannels);
    }

    /**
     * @param string[] $channels
     */
    private function createClient(bool $debug, array $channels): Client
    {
        return new Client(new ClientOptions([
            'options' => ['debug' => $debug],
            'connection' => [
                'secure' => true,
                'reconnect' => true,
                'rejoin' => true,
            ],
            'identity' => [
                'username' => $this->botNickname,
                'password' => $this->secret,
            ],
            'channels' => $channels,
        ]));
    }
}
