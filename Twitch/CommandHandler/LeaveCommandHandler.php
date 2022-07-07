<?php

namespace Twitch\CommandHandler;

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

    public function handle($args): ?string
    {
        $this->twitch->emit('[PART]', Twitch::LOG_INFO);
        $this->twitch->leaveChannel();

        return null;
    }
}
