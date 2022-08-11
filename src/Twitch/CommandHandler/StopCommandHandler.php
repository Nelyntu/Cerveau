<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;

class StopCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'stop';

    public function __construct(private readonly Twitch $twitch, private readonly UserList $userList)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $this->twitch->close();

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
