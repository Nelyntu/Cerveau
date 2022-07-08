<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;

class JoinCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'join';
    private Twitch $twitch;
    private UserList $userList;

    public function __construct(Twitch $twitch, UserList $userList)
    {
        $this->twitch = $twitch;
        $this->userList = $userList;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $channel = $command->arguments[1];
        if (!$channel) {
            return null;
        }
        $this->twitch->getIrcApi()->joinChannel($channel);

        return null;
    }

    public function isAuthorized($username): bool
    {
        return $username === $this->userList->streamer;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
