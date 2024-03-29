<?php

namespace Cerveau\Bot\CommandHandler;

use Cerveau\Bot\Command;
use Cerveau\Bot\UserList;
use Symfony\Contracts\Translation\TranslatorInterface;
use function in_array;

class SoCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'so';

    public function __construct(private readonly UserList $userList, private readonly TranslatorInterface $translator)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $userToSO = $command->arguments->firstArgument;
        if ($userToSO === null) {
            return null;
        }

        return $this->translator->trans('commands.so.message', ['%streamer%' => $userToSO,], 'commands');
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
