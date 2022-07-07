<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
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

    public function handle(Command $command): ?string
    {
        $userToSO = $command->arguments[1];
        $this->twitch->emit('[SO] ' . $userToSO, Twitch::LOG_INFO);
        if (!$userToSO) {
            return null;
        }

        return 'Hey, go check out ' . $userToSO . ' at https://www.twitch.tv/' . $userToSO . ' They are good peoples! Pretty good. Pretty good!';
    }
}
