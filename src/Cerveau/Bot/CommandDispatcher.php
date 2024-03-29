<?php

namespace Cerveau\Bot;

use Cerveau\Bot\CommandHandler\CommandHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides an easy way to handle commands.
 */
class CommandDispatcher
{
    /** @var CommandHandlerInterface[] */
    protected array $commands = [];

    /**
     * @param string[] $commandSymbols
     */
    public function __construct(private readonly array $commandSymbols, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param iterable<CommandHandlerInterface> $commands
     */
    public function setCommands(iterable $commands): void
    {
        $this->commands = [...$commands];
    }

    public function handle(Message $message): ?string
    {
        $command = $this->messageToCommand($message);

        if (!$command instanceof Command) {
            return null;
        }

        $commandName = $command->command;
        $this->logger->info("[CD][COMMAND] `" . $commandName . "`");
        $this->logger->info("[CD][ARGS] " . $command->arguments->text);

        $response = null;
        $found = false;

        foreach ($this->commands as $commandHandler) {
            if (!$commandHandler->supports($commandName)) {
                continue;
            }
            $found = true;
            $response = $commandHandler->handle($command);
        }

        if (!$found) {
            $this->logger->info("[CD][COMMAND] `$commandName` NOT HANDLED");
        }

        return $response;
    }

    /**
     * @return CommandHandlerInterface[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    private function findCommandSymbol(Message $message): ?string
    {
        foreach ($this->commandSymbols as $symbol) {
            if (str_starts_with($message->text, $symbol)) {
                return $symbol;
            }
        }

        return null;
    }

    private function messageToCommand(Message $message): ?Command
    {
        $commandSymbol = $this->findCommandSymbol($message);

        if ($commandSymbol === null) {
            return null;
        }

        $withoutSymbol = trim(substr($message->text, strlen($commandSymbol)));
        preg_match('/^([^ ]+)(?: +(.*))?$/', $withoutSymbol, $matches);

        $command = $matches[1] ?? null;

        if ($command === null) {
            return null;
        }

        $arguments = $matches[2] ?? null;

        return new Command($message->channel, $message->user, $command, Arguments::createFrom($arguments));
    }

    /**
     * @return string[]
     */
    public function getCommandSymbols(): array
    {
        return $this->commandSymbols;
    }
}
