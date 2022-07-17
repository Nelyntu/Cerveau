<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\IRCApi;
use Twitch\UserList;

class JoinCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'join';
    private UserList $userList;
    private IRCApi $IRCApi;

    public function __construct(IRCApi $IRCApi, UserList $userList)
    {
        $this->userList = $userList;
        $this->IRCApi = $IRCApi;
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
        $this->IRCApi->joinChannel($channel);

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
