<?php

namespace Twitch\CommandHandler;

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

    public function handle($args): ?string
    {
        $reason = '';
        for ($i=2, $iMax = count($args); $i< $iMax; $i++) {
            $reason .= $args[$i] . ' ';
        }
        $this->twitch->emit('[BAN] ' . $args[1] . " $reason", Twitch::LOG_INFO);
        $this->twitch->ban($args[1], trim($reason)); //ban with optional reason

        return null;
    }
}
