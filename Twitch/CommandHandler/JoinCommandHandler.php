<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
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

    public function handle(Command $command): ?string
    {
        $channel = $command->arguments[1];
        $this->twitch->emit('[JOIN]' . $channel, Twitch::LOG_INFO);
        if (!$channel) {
            return null;
        }
        $this->twitch->joinChannel($channel);

        return null;
    }
}
