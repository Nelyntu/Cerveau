<?php

namespace Cerveau\Bot\CommandHandler;

use Cerveau\Bot\Command;
use Cerveau\Bot\CommandDispatcher;

class HelpCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'help';

    public function __construct(private readonly CommandDispatcher $commandDispatcher)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $commandSymbols = $this->commandDispatcher->getCommandSymbols();
        $commands = '[Command Prefixes] ' . implode(', ', $commandSymbols) . ' ';

        $authorizedCommands = array_filter($this->commandDispatcher->getCommands(), fn(CommandHandlerInterface $commandHandler) => $commandHandler->isAuthorized($command->user));
        $commandNames = array_map(fn(CommandHandlerInterface $commandHandler) => $commandHandler->getName(), $authorizedCommands);

        return $commands . ('[Commands] ' . implode(', ', array_merge(...$commandNames)));
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
