<?php

namespace Cerveau\CommandHandler;

use Cerveau\Command;
use Cerveau\Bot;
use Cerveau\UserList;
use function in_array;

class BanCommandHandlerHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'ban';

    public function __construct(private readonly Bot $twitch, private readonly UserList $userList)
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

        $bannedUser = $command->arguments->firstArgument;
        $reason = $command->arguments->rest ?? '';
        $this->twitch->ban($command->channel, $bannedUser, $reason);

        return null;
    }

    public function isAuthorized(string $username): bool
    {
        return in_array($username, $this->userList->getAll(), true);
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
