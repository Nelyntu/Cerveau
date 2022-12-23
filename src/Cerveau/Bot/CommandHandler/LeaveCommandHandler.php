<?php

namespace Cerveau\Bot\CommandHandler;

use Cerveau\Bot\Command;
use Cerveau\Bot\UserList;
use GhostZero\Tmi;

class LeaveCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'leave';

    public function __construct(private readonly Tmi\Client $botClientIrc, private readonly UserList $userList)
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

        $this->botClientIrc->part($command->arguments->firstArgument);

        return null;
    }

    public function isAuthorized(string $username): bool
    {
        return $username === $this->userList->botNickname;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
