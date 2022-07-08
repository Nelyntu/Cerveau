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

    public function __construct(Twitch $twitch, int $logLevel)
    {
        $this->twitch = $twitch;
        $this->logLevel = $logLevel;
    }

    public function addCommand(CommandHandlerInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function handle(Command $command): ?string
    {
        $commandName = $command->command;
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
}
