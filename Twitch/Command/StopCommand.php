<?php

namespace Twitch\Command;

use Twitch\Twitch;

class StopCommand implements CommandInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'stop';
    }

    public function handle($args): ?string
    {
        $this->twitch->emit('[STOP]', Twitch::LOG_INFO);
        $this->twitch->close();

        return null;
    }
}