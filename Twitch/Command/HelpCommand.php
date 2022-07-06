<?php

namespace Twitch\Command;

use Twitch\Twitch;

class HelpCommand implements CommandInterface
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        $this->twitch = $twitch;
    }

    public function supports($name): bool
    {
        return $name === 'help';
    }

    public function handle($args): ?string
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
}
