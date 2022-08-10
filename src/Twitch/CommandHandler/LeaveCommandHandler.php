<?php

namespace Twitch\CommandHandler;

use GhostZero\Tmi;
use Twitch\Command;
use Twitch\UserList;

class LeaveCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'leave';
    private UserList $userList;
    private Tmi\Client $ircClient;

    public function __construct(Tmi\Client $ircClient, UserList $userList)
    {
        $this->userList = $userList;
        $this->ircClient = $ircClient;
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        if ($command->arguments->firstArgument === null) {
            return null;
        }

        $this->ircClient->part($command->arguments->firstArgument);

        return null;
    }

    public function isAuthorized(string $username): bool
    {
        return $username === $this->userList->streamer;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
