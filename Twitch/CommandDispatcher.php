<?php

/*
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

use Twitch\CommandHandler\CommandHandlerInterface;

/**
 * Provides an easy way to handle commands.
 */
class CommandDispatcher
{
    protected Twitch $twitch;
    protected int $logLevel;
    /** @var CommandHandlerInterface[] */
    protected array $commands = [];
    /** @var string[] */
    private array $commandSymbols;

    public function __construct(Twitch $twitch, array $commandSymbols, int $logLevel)
    {
        $this->twitch = $twitch;
        $this->logLevel = $logLevel;
        $this->commandSymbols = $commandSymbols;
    }

    public function addCommand(CommandHandlerInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function handle(Message $message): ?string
    {
        $commandSymbol = $this->findCommandSymbol($message);

        if ($commandSymbol === null) {
            return null;
        }

        $command = $this->toCommand($message, $commandSymbol);
        $commandName = $command->command;
        $this->twitch->emit("[COMMAND] `" . $commandName . "`", Twitch::LOG_INFO);
        $this->twitch->emit("[ARGS] " . print_r($command->arguments, true), Twitch::LOG_INFO);

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
            $this->twitch->emit("[HANDLE COMMAND] `$commandName` NOT HANDLED", Twitch::LOG_INFO);
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

    private function toCommand(Message $message, string $commandSymbol): Command
    {
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
