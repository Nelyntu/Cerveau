<?php

namespace Cerveau\CommandHandler;

use GhostZero\Tmi;
use Cerveau\Command;
use Cerveau\UserList;

class LeaveCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'leave';

    public function __construct(private readonly Tmi\Client $ircClient, private readonly UserList $userList)
    {
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
