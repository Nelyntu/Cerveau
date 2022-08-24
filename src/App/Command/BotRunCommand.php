<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Cerveau\Bot;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'bot:run')]
class BotRunCommand extends Command
{
    public function __construct(private readonly Bot $twitch)
    {
        parent::__construct(self::$defaultName);
    }

    protected static $defaultName = 'bot:run';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->twitch->run();

        return Command::SUCCESS;
    }
}
