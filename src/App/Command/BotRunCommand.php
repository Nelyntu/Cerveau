<?php

namespace App\Command;

use Cerveau\Bot\Bot;
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
        private readonly Client              $botClientIrc,
        private readonly TranslatorInterface $translator,
        private readonly array               $channels,
    )
    {
        parent::__construct(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // on bot start
        $this->botClientIrc->on(WelcomeEvent::class, function (): void {
            foreach ($this->channels as $channel) {
                $this->botClientIrc->say($channel, $this->translator->trans('bot.start', [], 'bot'));
            }
        });

        // on bot end (CTRL+C)
        if (defined('SIGINT')) {
            $loop = $this->botClientIrc->getLoop();
            $loop->addSignal(SIGINT, function () use ($loop) {
                foreach ($this->channels as $channel) {
                    $this->botClientIrc->say($channel, $this->translator->trans('bot.end', [], 'bot'));
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
