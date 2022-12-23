<?php

namespace Cerveau\Bot\CommandHandler\Hangman;

use Cerveau\Bot\Command;
use Cerveau\Bot\CommandHandler\CommandHandlerInterface;
use Cerveau\Bot\UserList;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class HangmanResetCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'hg_reset';

    public function __construct(private readonly UserList $userList, private readonly FilesystemAdapter $cache)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $this->cache->deleteItem(HangmanCommandHandler::DATA_CACHE_KEY);

        return 'ok';
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
