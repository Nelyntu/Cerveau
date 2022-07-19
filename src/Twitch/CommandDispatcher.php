<?php

namespace Twitch;

use Psr\Log\LoggerInterface;
use Twitch\CommandHandler\CommandHandlerInterface;

/**
 * Provides an easy way to handle commands.
 */
class CommandDispatcher
{
    /** @var CommandHandlerInterface[] */
    protected array $commands = [];
    /** @var string[] */
    private array $commandSymbols;
    private LoggerInterface $logger;

    public function __construct(array $commandSymbols, LoggerInterface $logger)
    {
        $this->commandSymbols = $commandSymbols;
        $this->logger = $logger;
    }

    public function addCommand(CommandHandlerInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function handle(Message $message): ?string
    {
        $command = $this->messageToCommand($message);

        if ($command === null) {
            return null;
        }

        $commandName = $command->command;
        $this->logger->info("[CD][COMMAND] `" . $commandName . "`");
        $this->logger->info("[CD][ARGS] " . implode(' ', $command->arguments));

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

    public function findCommandSymbol(Message $message): ?string
    {
        $commandSymbol = null;
        foreach ($this->commandSymbols as $symbol) {
            if (strpos($message->text, $symbol) === 0) {
                $commandSymbol = $symbol;
                break;
            }
        }
        return $commandSymbol;
    }

    private function messageToCommand(Message $message): ?Command
    {
        $commandSymbol = $this->findCommandSymbol($message);

        if ($commandSymbol === null) {
            return null;
        }

        $withoutSymbol = trim(substr($message->text, strlen($commandSymbol)));
        $dataArr = explode(' ', $withoutSymbol);
        $command = strtolower(trim($dataArr[0]));

        return new Command($message->channel, $message->user, $command, $dataArr);
    }

    public function getCommandSymbols(): array
    {
        return $this->commandSymbols;
    }
}
