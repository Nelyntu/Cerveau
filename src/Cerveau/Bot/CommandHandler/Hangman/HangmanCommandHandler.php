<?php

namespace Cerveau\Bot\CommandHandler\Hangman;

use Cerveau\Bot\Command;
use Cerveau\Bot\CommandHandler\CoolDownableCommandHandler;
use Symfony\Component\Cache\CacheItem;

class HangmanCommandHandler extends CoolDownableCommandHandler
{
    private const COMMAND_NAME = 'hg';
    private const MAX_TRY = 9;
    final public const DATA_CACHE_KEY = 'cerveau:command:hg:v3:data';

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $wordToFindItemCache = $this->getSessionCacheItem();
        /** @var HangmanSession $session */
        $session = $wordToFindItemCache->get();

        $suggestedLetter = $command->arguments->firstArgument;
        if ($suggestedLetter === null) {
            return $this->translator->trans(
                'commands.hg.situation',
                ['%wordFoundByUsers%' => $session->getWordFoundByUsers()],
                'commands');
        }

        if (!is_string($suggestedLetter) || strlen($suggestedLetter) > 1) {
            return $this->translator->trans('commands.hg.invalid_letter', [], 'commands');
        }

        $suggestedLetter = mb_strtoupper($suggestedLetter);

        $coolDownCheck = $this->checkUserCoolDown($command);
        if (is_string($coolDownCheck)) {
            return $coolDownCheck;
        }

        if ($session->isLetterAlreadySuggested($suggestedLetter)) {
            return $this->translator->trans(
                'commands.hg.already_suggested_letter',
                ['%suggestedLetter%' => $suggestedLetter],
                'commands');
        }

        if ($session->suggestLetter($suggestedLetter)) {
            $responseToProposition = $this->translator->trans(
                'commands.hg.succeed_suggested_letter',
                ['%suggestedLetter%' => $suggestedLetter,],
                'commands');
        } else {
            $fails = $session->getFails();
            if ($fails === self::MAX_TRY) {
                $this->cache->deleteItem(self::DATA_CACHE_KEY);

                return $this->translator->trans(
                    'commands.hg.game_over',
                    ['%wordToFind%' => $session->getWordToFind()],
                    'commands');
            }
            $responseToProposition = $this->translator->trans(
                'commands.hg.failed_suggested_letter',
                ['%suggestedLetter%' => $suggestedLetter, '%tries%' => (self::MAX_TRY - $fails)],
                'commands');
        }

        $wordToFindItemCache->set($session);
        $this->cache->save($wordToFindItemCache);

        if ($session->isWordFound()) {
            $this->cache->deleteItem(self::DATA_CACHE_KEY);

            return $this->translator->trans(
                'commands.hg.win',
                ['%wordToFind%' => $session->getWordToFind()],
                'commands');
        }

        return $responseToProposition . $this->translator->trans(
                'commands.hg.reminder',
                ['%wordFoundByUsers%' => $session->getWordFoundByUsers()],
                'commands');
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }

    private function getRandomWord(): string
    {
        $locale = $this->translator->getLocale();
        /** @var non-empty-array<int, string> $possibleWords */
        $possibleWords = file(__DIR__ . '/words.' . $locale . '.txt', FILE_IGNORE_NEW_LINES);

        return mb_strtoupper($possibleWords[random_int(0, count($possibleWords) - 1)]);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getSessionCacheItem(): CacheItem
    {
        $wordToFindItemCache = $this->cache->getItem(self::DATA_CACHE_KEY);
        if (!$wordToFindItemCache->isHit()) {
            // new word !
            $wordToFind = $this->getRandomWord();
            $session = new HangmanSession($wordToFind);

            $wordToFindItemCache->expiresAfter(3600 * 5);
            $wordToFindItemCache->set($session);
            $this->cache->save($wordToFindItemCache);
        }

        return $wordToFindItemCache;
    }
}
