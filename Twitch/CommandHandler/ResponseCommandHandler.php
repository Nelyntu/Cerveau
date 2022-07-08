<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class ResponseCommandHandler implements CommandHandlerInterface
{
    private Twitch $twitch;
    /** @var string[] */
    private array $responses;

    public function __construct(Twitch $twitch, array $responses)
    {
        $this->twitch = $twitch;
        $this->responses = $responses;
    }

    public function supports($name): bool
    {
        return array_key_exists($name, $this->responses);
    }

    public function handle(Command $command): ?string
    {
        $this->twitch->emit('[RESPONSE]', Twitch::LOG_INFO);

        return $this->responses[$command->command];
    }

    public function isAuthorized($username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return array_keys($this->responses);
    }
}
