<?php

namespace Twitch\CommandHandler;

use Twitch\Twitch;

class SoCommandHandler implements CommandHandlerInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'so';
    }

    public function handle($args): ?string
    {
        $this->twitch->emit('[SO] ' . $args[1], Twitch::LOG_INFO);
        if (!$args[1]) {
            return null;
        }

        return 'Hey, go check out ' . $args[1] . ' at https://www.twitch.tv/' . $args[1] . ' They are good peoples! Pretty good. Pretty good!';
    }
}
