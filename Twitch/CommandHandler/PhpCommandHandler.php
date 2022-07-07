<?php

namespace Twitch\CommandHandler;

use Twitch\Twitch;

class PhpCommandHandler implements CommandHandlerInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'php';
    }

    public function handle($args): ?string
    {
        $this->twitch->emit('[PHP]', Twitch::LOG_INFO);
        return 'Current PHP version: ' . PHP_VERSION;
    }
}
