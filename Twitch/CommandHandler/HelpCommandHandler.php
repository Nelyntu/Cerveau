<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class HelpCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'help';
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $commandSymbols = $this->twitch->getCommandSymbols();
        $commands = '[Command Prefixes] ' . implode(', ', $commandSymbols) . ' ';

        $authorizedCommands = array_filter($this->twitch->getCommands()->getCommands(), fn(CommandHandlerInterface $commandHandler) => $commandHandler->isAuthorized($command->user));
        $commandNames = array_map(fn(CommandHandlerInterface $commandHandler) => $commandHandler->getName(), $authorizedCommands);

        $commands .= '[Commands] ' . implode(', ', array_merge(...$commandNames));

        $this->twitch->emit("[COMMANDS] `$commands`", Twitch::LOG_INFO);

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
