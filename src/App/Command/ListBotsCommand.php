<?php

namespace App\Command;

use Cerveau\Statistics\Channel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cerveau:bots:list')]
class ListBotsCommand extends Command
{
    public function __construct(private readonly Channel $channelStat)
    {
        parent::__construct(self::$defaultName);
    }

    public function configure(): void
    {
        $this
            ->addArgument('channel', InputArgument::REQUIRED, 'channel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $channel */
        $channel = $input->getArgument('channel');

        $bots = $this->channelStat->getBots($channel);

        $output->writeln(implode(', ', $bots));
        $output->writeln(count($bots).' bots');

        return 0;
    }
}
