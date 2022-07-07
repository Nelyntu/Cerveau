<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class StopCommandHandler implements CommandHandlerInterface
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

    public function handle(Command $command): ?string
    {
        $this->twitch->emit('[STOP]', Twitch::LOG_INFO);
        $this->twitch->close();

        return null;
    }
}
