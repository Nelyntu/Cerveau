<?php

namespace App\Command;

use Cerveau\Statistics\StatisticsGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cerveau:channel:stats')]
class StatsCommand extends Command
{
    public function __construct(private readonly StatisticsGenerator $statisticsGenerator)
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
        $start = new \DateTimeImmutable('now - 3 weeks');
        $end = new \DateTimeImmutable();

        $statistics = $this->statisticsGenerator->generate($channel, $start, $end);

        $output->writeln('#chatters: ' . $statistics->chattersCount);
        $output->writeln('#followers: ' . $statistics->followersCount);

        foreach ($statistics->sessions as $session) {
            /** @phpstan-ignore-next-line */
            $output->writeln($session->start->format('Y-m-d H:i:s') . ' -> ' . $session->end->format('Y-m-d H:i:s'));
        }

        return 0;
    }
}
