<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twitch\Twitch;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'bot:run')]
class BotRunCommand extends Command
{
    private Twitch $twitch;

    public function __construct(Twitch $twitch)
    {
        parent::__construct(self::$defaultName);
        $this->twitch = $twitch;
    }

    protected static $defaultName = 'bot:run';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ... put here the code to create the user

        $this->twitch->run();

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}
