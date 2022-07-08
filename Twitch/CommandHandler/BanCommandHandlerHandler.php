<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;
use function in_array;

class BanCommandHandlerHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'ban';
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
        $reason = '';
        for ($i = 2, $iMax = count($command->arguments); $i < $iMax; $i++) {
            $reason .= $command->arguments[$i] . ' ';
        }
        $bannedUser = $command->arguments[1];
        $this->twitch->getIrcApi()->ban($bannedUser, trim($reason));

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
