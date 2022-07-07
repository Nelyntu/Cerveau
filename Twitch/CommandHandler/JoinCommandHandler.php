<?php

namespace Twitch\CommandHandler;

use Twitch\Twitch;

class JoinCommandHandler implements CommandHandlerInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'join';
    }

    public function handle($args): ?string
    {
        $this->twitch->emit('[JOIN]' . $args[1], Twitch::LOG_INFO);
        if (!$args[1]) {
            return null;
        }
        $this->twitch->joinChannel($args[1]);

        return null;
    }
}
