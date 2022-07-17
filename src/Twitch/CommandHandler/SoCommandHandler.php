<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;
use function in_array;

class SoCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'so';
    private UserList $userList;

    public function __construct(UserList $userList)
    {
        $this->userList = $userList;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $userToSO = $command->arguments[1];
        if (!$userToSO) {
            return null;
        }

        return 'Hey, go check out ' . $userToSO . ' at https://www.twitch.tv/' . $userToSO . ' They are good peoples! Pretty good. Pretty good!';
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
