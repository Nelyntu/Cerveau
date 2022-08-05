<?php

namespace Twitch\CommandHandler;

use GhostZero\Tmi;
use Twitch\Command;
use Twitch\UserList;

class JoinCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'join';
    private UserList $userList;
    private Tmi\Client $ircClient;

    public function __construct(Tmi\Client $ircClient, UserList $userList)
    {
        $this->userList = $userList;
        $this->ircClient = $ircClient;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $channel = $command->arguments->firstArgument;
        if ($channel === null) {
            return null;
        }
        $this->ircClient->join($channel);

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
