<?php

namespace App\Command;

use Cerveau\AutoMessage;
use Cerveau\Bot;
use Cerveau\Factory\EventTrackerFactory;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\Events\Irc\WelcomeEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'cerveau:bot:run')]
class BotRunCommand extends Command
{
    /**
     * @param string[] $channels
     */
    public function __construct(
        private readonly Bot                 $bot,
        private readonly Client              $client,
        private readonly TranslatorInterface $translator,
        private readonly AutoMessage         $autoMessage,
        private readonly EventTrackerFactory $statisticsFactory,
        private readonly array               $channels,
    )
    {
        parent::__construct(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // on bot start
        $this->client->on(WelcomeEvent::class, function (WelcomeEvent $e): void {
            foreach($this->channels as $channel) {
                $this->client->say($channel, $this->translator->trans('bot.start', [], 'bot'));

                // start auto message
                $this->autoMessage->start();
                $this->statisticsFactory->create()->startTracking($channel);
            }
        });

        // on bot end (CTRL+C)
        $loop = $this->client->getLoop();
        $loop->addSignal(SIGINT, function (int $signal) use ($loop) {
            foreach($this->channels as $channel) {
                $this->client->say($channel, $this->translator->trans('bot.end', [], 'bot'));
                $loop->futureTick(function () use ($loop): void {
                    $loop->stop();
                });
            }
        });

        // start
        $this->bot->run();

        return Command::SUCCESS;
    }
}
