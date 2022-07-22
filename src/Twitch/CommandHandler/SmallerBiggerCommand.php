<?php

namespace Twitch\CommandHandler;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Twitch\Command;

class SmallerBiggerCommand implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'sb';
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
        if (!array_key_exists(1, $command->arguments)) {
            return 'tu dois proposer un nombre. Par exemple : !sb 42';
        }

        $suggestedValue = $command->arguments[1];

        if (!is_numeric($suggestedValue)) {
            return 'c\'est un nombre qu\'il faut proposer 😅. Tu sais ... des chiffres ... 😎';
        }

        $suggestedValue = (int)$suggestedValue;

        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:sb:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            $remainingCoolDownTime = (int)($coolDownItemCache->get() - microtime(true));
            return 'TU TE CALMES ! Tu peux retenter dans ' . $remainingCoolDownTime . 's';
        }
        $coolDownItemCache->expiresAfter(30);
        $coolDownItemCache->set(30 + microtime(true));
        $this->cache->save($coolDownItemCache);

        // retrieve value

        $valueToFindItemCache = $this->cache->getItem('cerveau:command:sb:value');
        if (!$valueToFindItemCache->isHit()) {
            // new value !
            $valueToFind = rand(0, 1_000);
            $valueToFindItemCache->set($valueToFind);
            $valueToFindItemCache->expiresAfter(3600 * 5);
            $this->cache->save($valueToFindItemCache);
        } else {
            $valueToFind = $valueToFindItemCache->get();
        }

        if ($suggestedValue > $valueToFind) {
            return 'non, c\'est plus petit.';
        }

        if ($suggestedValue < $valueToFind) {
            return 'non, c\'est plus grand.';
        }

        $this->cache->deleteItem('cerveau:command:sb:value');

        return 'tu as trouvé ! C\'était effectivement ' . $valueToFind . '.';
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
