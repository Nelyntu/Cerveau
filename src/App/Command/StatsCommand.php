<?php

namespace App\Command;

use Cerveau\Statistics\StatisticsGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
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

        $rows = [];
        foreach ($statistics->sessions as $session) {
            $rows[] = [$session->start->format('Y-m-d H:i:s'), $session->end->format('H:i:s'), count($session->chatters), round($session->avgView, 1),];
        }

        $rows[] = new TableSeparator();
        $rows[] = [
            new TableCell('#followers: ' . $statistics->followersCount, ['colspan' => 2, 'style' => new TableCellStyle(['align' => 'center'])]),
            $statistics->chattersCount,
            round($statistics->avgView, 1),
        ];

        $table = new Table($output);
        $table
            ->setHeaders(['Start', 'End', '#chatters', 'avg'])
            ->setRows($rows);
        $table->render();

        return 0;
    }
}
