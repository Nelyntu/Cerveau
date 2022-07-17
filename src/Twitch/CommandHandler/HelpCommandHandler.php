<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\CommandDispatcher;

class HelpCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'help';
    private CommandDispatcher $commandDispatcher;

    public function __construct(CommandDispatcher $commandDispatcher)
    {
        $this->commandDispatcher = $commandDispatcher;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $commandSymbols = $this->commandDispatcher->getCommandSymbols();
        $commands = '[Command Prefixes] ' . implode(', ', $commandSymbols) . ' ';

        $authorizedCommands = array_filter($this->commandDispatcher->getCommands(), fn(CommandHandlerInterface $commandHandler) => $commandHandler->isAuthorized($command->user));
        $commandNames = array_map(fn(CommandHandlerInterface $commandHandler) => $commandHandler->getName(), $authorizedCommands);

        $commands .= '[Commands] ' . implode(', ', array_merge(...$commandNames));

        return $commands;
    }

    public function isAuthorized($username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
