<?php

namespace App\Command;

use Cerveau\Bot;
use Cerveau\OnStart;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\Events\Irc\WelcomeEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'bot:run')]
class BotRunCommand extends Command
{
    public function __construct(
        private readonly Bot $twitch,
        private readonly Client $client,
        private readonly string $streamer,
        protected readonly TranslatorInterface $translator
    ) {
        parent::__construct(self::$defaultName);
    }

    protected static $defaultName = 'bot:run';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // on bot start
        $this->client->on(WelcomeEvent::class, function (WelcomeEvent $e): void {
            $this->client->say($this->streamer, $this->translator->trans('bot.start', [], 'bot'));
        });

        // on bot end (CTRL+C)
        $loop = $this->client->getLoop();
        $loop->addSignal(SIGINT, function (int $signal) use ($loop) {
            $this->client->say($this->streamer, $this->translator->trans('bot.end', [], 'bot'));
            $loop->futureTick(function () use ($loop): void {
                $loop->stop();
            });
        });

        // start
        $this->twitch->run();

        return Command::SUCCESS;
    }
}
