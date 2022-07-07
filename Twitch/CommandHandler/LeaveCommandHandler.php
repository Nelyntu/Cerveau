<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class LeaveCommandHandler implements CommandHandlerInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'leave';
    }

    public function handle(Command $command): ?string
    {
        $this->twitch->emit('[PART]', Twitch::LOG_INFO);
        $this->twitch->getIrcApi()->leaveChannel($command->channel);

        return null;
    }
}
