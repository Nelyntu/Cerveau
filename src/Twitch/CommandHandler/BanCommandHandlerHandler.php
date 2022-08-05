<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;
use function in_array;

class BanCommandHandlerHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'ban';
    private UserList $userList;
    private Twitch $twitch;

    public function __construct(Twitch $twitch, UserList $userList)
    {
        $this->userList = $userList;
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $bannedUser = $command->arguments->firstArgument;
        $reason = $command->arguments->rest;
        $this->twitch->ban($command->channel, $bannedUser, $reason);

        return null;
    }

    public function isAuthorized($username): bool
    {
        return in_array($username, $this->userList->getAll(), true);
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
