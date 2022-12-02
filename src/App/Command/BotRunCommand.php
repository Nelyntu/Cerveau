<?php

namespace App\Command;

use Cerveau\AutoMessage;
use Cerveau\Bot;
use Cerveau\Factory\EventTrackerFactory;
use Cerveau\Factory\LiveChannelViewersFactory;
use Cerveau\Statistics\LiveStat;
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
        private readonly Bot                       $bot,
        private readonly Client                    $client,
        private readonly TranslatorInterface       $translator,
        private readonly AutoMessage               $autoMessage,
        private readonly EventTrackerFactory       $statisticsFactory,
        private readonly array                     $channels,
        private readonly array                     $statsChannels,
        private readonly LiveChannelViewersFactory $liveChannelViewersFactory,
    )
    {
        parent::__construct(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cursor = new Cursor($output);
        $cursor->clearScreen();
        // on bot start
        $this->client->on(WelcomeEvent::class, function (WelcomeEvent $e) use ($cursor, $output): void {
            foreach ($this->channels as $channel) {
                $this->client->say($channel, $this->translator->trans('bot.start', [], 'bot'));
            }

            // start auto message
            $this->autoMessage->start();

            $statsChannels = $this->statsChannels;
            foreach ($statsChannels as $statsChannel) {
                // event tracker
                $eventTracker = $this->statisticsFactory->create();
                $eventTracker->startTracking($statsChannel);

                // live channel viewers
                $liveChannelViewers = $this->liveChannelViewersFactory->create();
                $viewersEmitter = $liveChannelViewers->getEmitter();

                $viewersEmitter->on('live_channel_viewers.chatters_updated',
                    function (string $channel, LiveStat $liveStat) use ($cursor, $statsChannels, $output) {
                        $channelPosition = (int) array_search($channel, $statsChannels);
                        $cursor->moveToPosition(1, $channelPosition);
                        $cursor->clearLineAfter();
                        $output->write($channel.': '.$liveStat->chatterCount. ' / '.$liveStat->botCount);
                });

                $liveChannelViewers->startTracking($statsChannel);
            }
        });

        // on bot end (CTRL+C)
        if (defined('SIGINT')) {
            $loop = $this->client->getLoop();
            $loop->addSignal(SIGINT, function (int $signal) use ($loop) {
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
