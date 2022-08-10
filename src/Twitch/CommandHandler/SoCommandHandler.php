<?php

namespace Twitch\CommandHandler;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twitch\Command;
use Twitch\Twitch;
use Twitch\UserList;
use function in_array;

class SoCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'so';
    private UserList $userList;
    private TranslatorInterface $translator;

    public function __construct(UserList $userList, TranslatorInterface $translator)
    {
        $this->userList = $userList;
        $this->translator = $translator;
    }

    public function supports($name): bool
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

    public function isAuthorized($username): bool
    {
        return in_array($username, $this->userList->getAll(), true);
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
