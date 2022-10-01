<?php

namespace App\Command;

use Cerveau\Statistics\Channel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cerveau:chatters:list')]
class ListRealChattersCommand extends Command
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

        $realChatters = $this->channelStat->getRealChatters($channel);

        $output->writeln(implode(', ', $realChatters));
        $output->writeln(count($realChatters).' chatters');
        return 0;
    }
}
