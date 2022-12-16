<?php

namespace App\LiveStats;

use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Output\OutputInterface;

class Table
{
    /** @var Row[] */
    private array $rows;

    public function addRow(Row $row): void
    {
        $this->rows[] = $row;
    }

    public function toSymfony(OutputInterface $output): ConsoleTable
    {
        $rows = array_map(fn(Row $row) => [$row->channel, $row->chatters, $row->data,], $this->rows);

        $table = new ConsoleTable($output);
        $table
            ->setHeaders(['Channel', 'Chatters', 'Data'])
            ->setRows($rows);

        return $table;
    }
}
