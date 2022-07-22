<?php

namespace Twitch\CommandHandler;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Twitch\Command;

class HeightBallCommand implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'question';
    private FilesystemAdapter $cache;

    public function __construct(FilesystemAdapter $cache)
    {
        $this->cache = $cache;
    }

    public function supports($name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:question:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            return 'TU TE CALMES ! RESPIRE UN PEU AVANT DE REPOSER UNE QUESTION !';
        }

        $coolDownItemCache->expiresAfter(30);
        $this->cache->save($coolDownItemCache);

        // main code
        $possibleResponses = [
            'Oui',
            'Non',
            'ça marchera !',
            'Aucune chance...',
            'Vas-y, fonce 🤗',
            'Plutôt l\'enfer 👿',
        ];

        $responseIndex = rand(0, count($possibleResponses) - 1);

        return $possibleResponses[$responseIndex];
    }

    public function isAuthorized($username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
