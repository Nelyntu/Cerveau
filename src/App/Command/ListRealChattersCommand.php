<?php

namespace App\Command;

use Cerveau\Repository\UserRepository;
use Cerveau\Statistics\Channel;
use Cerveau\Twitch\Follower;
use Cerveau\Twitch\Twitch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cerveau:chatters:list')]
class ListRealChattersCommand extends Command
{
    public function __construct(
        private readonly Channel        $channelStat,
        private readonly Twitch         $twitch,
        private readonly UserRepository $userRepository,
    )
    {
        parent::__construct(self::$defaultName);
    }

    public function configure(): void
    {
        $this
            ->addArgument('channel', InputArgument::REQUIRED, 'channel');
    }

    /**
     * @param array<Follower> $followers
     * @param string[] $realChatters
     * @return array<array<string|TableCell>|TableSeparator>
     */
    public function getRows(array $followers, array $realChatters): array
    {
        $followersByName = [];
        foreach ($followers as $follower) {
            $followersByName[$follower->login] = 1;
        }

        // data
        $rows = [];
        foreach ($realChatters as $chatter) {
            $rows[] = [$chatter, isset($followersByName[$chatter]) ? '✔' : '',];
        }

        // end table
        $rows[] = new TableSeparator();
        $rows[] = [new TableCell(count($realChatters) . ' chatters', ['colspan' => 2, 'style' => new TableCellStyle(['align' => 'center'])])];
        return $rows;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $channel */
        $channel = $input->getArgument('channel');

        $channelUser = $this->userRepository->getOrCreateByUsername($channel);
        $realChatters = $this->channelStat->getRealChatters($channel);
        $followers = $this->twitch->getFollowers($channelUser);

        $rows = $this->getRows($followers, $realChatters);

        $table = new Table($output);
        $table
            ->setHeaders(['Chatter name', 'Follow ?'])
            ->setRows($rows);
        $table->render();

        return 0;
    }
}
