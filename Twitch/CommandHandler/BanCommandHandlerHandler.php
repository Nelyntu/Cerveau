<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class BanCommandHandlerHandler implements CommandHandlerInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'ban';
    }

    public function handle(Command $command): ?string
    {
        $reason = '';
        for ($i = 2, $iMax = count($command->arguments); $i < $iMax; $i++) {
            $reason .= $command->arguments[$i] . ' ';
        }
        $bannedUser = $command->arguments[1];
        $this->twitch->emit('[BAN] ' . $bannedUser . " $reason", Twitch::LOG_INFO);
        $this->twitch->getIrcApi()->ban($bannedUser, trim($reason));

        return null;
    }
}
