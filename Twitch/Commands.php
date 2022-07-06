<?php

/*
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

use Twitch\Command\CommandInterface;

/**
 * Provides an easy way to have triggerable commands.
 */
class Commands
{
	protected Twitch $twitch;
	protected int $logLevel;
    /** @var CommandInterface[] */
    protected array $commands = [];

	public function __construct(Twitch $twitch, int $logLevel)
	{
		$this->twitch = $twitch;
		$this->logLevel = $logLevel;
	}

    public function addCommand(CommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function handle(string $command, ?array $args = []): ?string
    {
        $this->twitch->emit("[HANDLE COMMAND] `$command`", Twitch::LOG_INFO);
        $this->twitch->emit("[ARGS] ".print_r($args, true), Twitch::LOG_INFO);

		if($this->logLevel === Twitch::LOG_DEBUG) {
		$i = 0;
		foreach ($args as $arg) {
			$args[$i] = preg_replace('/[^A-Za-z0-9\-]/', '', trim($arg));
			$i++;
		}
		unset($i);
		}

        $response = null;
        $found = false;

        foreach($this->commands as $commandHandler) {
            if(!$commandHandler->supports($command)) {
                continue;
            }
            $found = true;
            $response = $commandHandler->handle($args);
        }

        if (!$found) {
            $this->twitch->emit("[HANDLE COMMAND] `$command` NOT FOUND", Twitch::LOG_INFO);
        }

        return $response;
    }
}
