<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;

class PhpCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'php';
    private UserList $userList;

    public function __construct(UserList $userList)
    {
        $this->userList = $userList;
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
        return $username === $this->userList->streamer;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
