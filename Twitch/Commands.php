<?php

/*
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

/**
 * Provides an easy way to have triggerable commands.
 */
class Commands
{
	protected Twitch $twitch;
	protected int $logLevel;

	public function __construct(Twitch $twitch, int $logLevel)
	{
		$this->twitch = $twitch;
		$this->logLevel = $logLevel;
	}

    public function handle(string $command, ?array $args = []): ?string
	{
        $response = null;

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

		if ($command === 'help')
		{
            $commandSymbols = $this->twitch->getCommandSymbols();
			$responses = $this->twitch->getResponses();
			$functions = $this->twitch->getFunctions();
            $restrictedFunctions = $this->twitch->getRestrictedFunctions();
            $privateFunctions = $this->twitch->getPrivateFunctions();

            $commands = '';
            if ($commandSymbols) {
                $commands .= '[Command Prefix] ' . implode(', ', $commandSymbols) . " ";
            }

            $publicCommands = array_merge(array_keys($responses), $functions);
            if (!empty($publicCommands)) {
                $commands .= '[Public] ' . implode(', ', $publicCommands) . ' ';
            }
            if (!empty($restrictedFunctions)) {
                $commands .= '[Whitelisted] ' . implode(', ', $restrictedFunctions) . ' ';
            }
            if (!empty($privateFunctions)) {
                $commands .= '[Private] ' . implode(', ', $privateFunctions) . ' ';
            }

			$this->twitch->emit("[COMMANDS] `$commands`", Twitch::LOG_INFO);
			return $commands;
		}

		if ($command === 'php')
		{
			$this->twitch->emit('[PHP]', Twitch::LOG_INFO);
			$response = 'Current PHP version: ' . phpversion();
		}

		if ($command === 'stop')
		{
			$this->twitch->emit('[STOP]', Twitch::LOG_INFO);
			$this->twitch->close();
		}

		if ($command === 'join')
		{
			$this->twitch->emit('[JOIN]' . $args[1], Twitch::LOG_INFO);
			if (!$args[1]) return null;
			$this->twitch->joinChannel($args[1]);
		}

		if ($command === 'leave')
		{
			$this->twitch->emit('[PART]', Twitch::LOG_INFO);
			$this->twitch->leaveChannel();
		}

		if ($command === 'so')
		{
			$this->twitch->emit('[SO] ' . $args[1], Twitch::LOG_INFO);
			if (!$args[1]) return null;
			$this->twitch->sendMessage('Hey, go check out ' . $args[1] . ' at https://www.twitch.tv/' . $args[1] . ' They are good peoples! Pretty good. Pretty good!');
		}

        if ($command === 'ban') {
			$reason = '';
			for ($i=2; $i<count($args); $i++) {
				$reason .= $args[$i] . ' ';
			}
			$this->twitch->emit('[BAN] ' . $args[1] . " $reason", Twitch::LOG_INFO);
			$this->twitch->ban($args[1], trim($reason)); //ban with optional reason
		}

		return $response;
	}
}
