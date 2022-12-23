<?php

namespace App\Command;

use App\LiveStats\Row;
use App\LiveStats\Table;
use Cerveau\Bot\AutoMessage;
use Cerveau\Bot\Bot;
use Cerveau\Factory\EventTrackerFactory;
use Cerveau\Live\Event\ChattersCountUpdatedEvent;
use Cerveau\Live\Event\NotFollowerJoinedEvent;
use Cerveau\Live\Event\NotFollowerLeftEvent;
use Cerveau\Live\Factory\ChattersCountUpdateTrackerFactory;
use Cerveau\Live\Factory\NotFollowerTrackerFactory;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\Events\Irc\WelcomeEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'cerveau:bot:run')]
class BotRunCommand extends Command
{
    /**
     * @param string[] $channels
     * @param string[] $statsChannels
     */
    public function __construct(
        private readonly Bot                               $bot,
        private readonly Client                            $client,
        private readonly TranslatorInterface               $translator,
        private readonly AutoMessage                       $autoMessage,
        private readonly EventTrackerFactory               $statisticsFactory,
        private readonly array                             $channels,
        private readonly array                             $statsChannels,
        private readonly ChattersCountUpdateTrackerFactory $liveChannelViewersFactory,
        private readonly NotFollowerTrackerFactory         $liveNotFollowerFactory
    )
    {
        parent::__construct(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cursor = new Cursor($output);
        $cursor->clearScreen();
        // on bot start
        $this->client->on(WelcomeEvent::class, function () use ($cursor, $output): void {
            $table = new Table();
            foreach ($this->channels as $channel) {
                $this->client->say($channel, $this->translator->trans('bot.start', [], 'bot'));
            }

            // start auto message
            $this->autoMessage->start();

            $statsChannels = $this->statsChannels;
            foreach ($statsChannels as $statsChannel) {
                $row = new Row($statsChannel);
                $table->addRow($row);

                // event tracker
                $eventTracker = $this->statisticsFactory->create();
                $eventTracker->startTracking($statsChannel);

                // live channel viewers
                $liveChannelViewers = $this->liveChannelViewersFactory->create();
                $viewersEmitter = $liveChannelViewers->getEmitter();

                $viewersEmitter->on('live_channel_viewers.chatters_updated',
                    function (string $channel, ChattersCountUpdatedEvent $liveStat) use ($table, $row, $cursor, $output) {
                        $row->chatters = $liveStat->chatterCount . ' / ' . $liveStat->botCount;
                        $cursor->clearScreen();
                        $cursor->moveToPosition(1, 0);
                        $table->toSymfony($output)->render();
                    });

                $liveChannelViewers->startTracking($statsChannel);

                // live not follower
                $liveNotFollower = $this->liveNotFollowerFactory->create();
                $notViewerEmitter = $liveNotFollower->getEmitter();

                $notViewerEmitter->on('live_chatter_not_follower.joined',
                    function (string $channel, NotFollowerJoinedEvent $liveStat) use ($table, $row, $cursor, $output) {
                        $row->data = '>>> ' . $liveStat->username . ' ' . ($liveStat->lastSeen !== null ? $liveStat->lastSeen->format('y-m-d H:i') : 'never seen');
                        $cursor->clearScreen();
                        $cursor->moveToPosition(1, 0);
                        $table->toSymfony($output)->render();
                    });

                $notViewerEmitter->on('live_chatter_not_follower.left',
                    function (string $channel, NotFollowerLeftEvent $liveStat) use ($table, $row, $cursor, $output) {
                        $row->data = '<<< ' . $liveStat->username;
                        $cursor->clearScreen();
                        $cursor->moveToPosition(1, 0);
                        $table->toSymfony($output)->render();
                    });

                $liveNotFollower->startTracking($statsChannel);
            }
        });

        // on bot end (CTRL+C)
        if (defined('SIGINT')) {
            $loop = $this->client->getLoop();
            $loop->addSignal(SIGINT, function () use ($loop) {
                foreach ($this->channels as $channel) {
                    $this->client->say($channel, $this->translator->trans('bot.end', [], 'bot'));
                    $loop->futureTick(function () use ($loop): void {
                        $loop->stop();
                    });
                }
            });
        }

        // start
        $this->bot->run();

        return Command::SUCCESS;
    }
}
