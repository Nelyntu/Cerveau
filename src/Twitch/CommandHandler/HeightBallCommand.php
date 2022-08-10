<?php

namespace Twitch\CommandHandler;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twitch\Command;

class HeightBallCommand implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'question';
    private FilesystemAdapter $cache;
    private TranslatorInterface $translator;

    public function __construct(FilesystemAdapter $cache, TranslatorInterface $translator)
    {
        $this->cache = $cache;
        $this->translator = $translator;
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:question:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            return $this->translator->trans('commands.question.triggered_cooldown', [], 'commands');
        }

        $coolDownItemCache->expiresAfter(30);
        $this->cache->save($coolDownItemCache);

        // main code
        $possibleResponses = [
            'yes.1',
            'yes.2',
            'yes.3',
            'no.1',
            'no.2',
            'no.3',
        ];

        $responseIndex = rand(0, count($possibleResponses) - 1);

        $transkey = $possibleResponses[$responseIndex];

        return $this->translator->trans('commands.question.' . $transkey, [], 'commands');
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
