<?php

namespace Cerveau\Bot\CommandHandler;

use Cerveau\Bot\Command;
use Cerveau\Bot\UserList;

class PhpCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'php';

    public function __construct(private readonly UserList $userList)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        return 'Current PHP version: ' . PHP_VERSION;
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
