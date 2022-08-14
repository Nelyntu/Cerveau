<?php

namespace Twitch\CommandHandler\Hangman;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twitch\Command;
use Twitch\CommandHandler\CommandHandlerInterface;

class HangmanCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'hg';
    public const DATA_CACHE_KEY = 'cerveau:command:hg:v3:data';

    public function __construct(private readonly FilesystemAdapter $cache, private readonly TranslatorInterface $translator)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $wordToFindItemCache = $this->retrieveWord();
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
            return $this->translator->trans( 'commands.hg.invalid_letter', [], 'commands');
        }

        $suggestedLetter = mb_strtoupper($suggestedLetter);

        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:hg:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            $remainingCoolDownTime = (int)($coolDownItemCache->get() - microtime(true));

            return $this->translator->trans('commands.hg.triggered_cooldown', ['%remainingCoolDownTime%' => $remainingCoolDownTime], 'commands');
        }

        $coolDownItemCache->expiresAfter(1);
        $coolDownItemCache->set(1 + microtime(true));
        $this->cache->save($coolDownItemCache);

        if ($session->isLetterAlreadySuggested($suggestedLetter)) {
            return $this->translator->trans( 'commands.hg.already_suggested_letter', ['%suggestedLetter%' => $suggestedLetter], 'commands');
        }

        if ($session->suggestLetter($suggestedLetter)) {
            $responseToProposition = $this->translator->trans( 'commands.hg.succeed_suggested_letter', ['%suggestedLetter%' => $suggestedLetter,], 'commands');

        } else {
            $fails = $session->getFails();
            if ($fails === 9) {
                $this->cache->deleteItem(self::DATA_CACHE_KEY);

                return $this->translator->trans( 'commands.hg.game_over', ['%wordToFind%' => $session->getWordToFind()], 'commands');
            }
            $responseToProposition = $this->translator->trans( 'commands.hg.failed_suggested_letter', ['%suggestedLetter%' => $suggestedLetter, '%tries%' => (9 - $fails)], 'commands');
        }

        $wordToFindItemCache->set($session);
        $this->cache->save($wordToFindItemCache);

        if ($session->isWordFound()) {
            $this->cache->deleteItem(self::DATA_CACHE_KEY);

            return $this->translator->trans( 'commands.hg.win', ['%wordToFind%' => $session->getWordToFind()], 'commands');
        }

        return $responseToProposition . $this->translator->trans( 'commands.hg.reminder', ['%wordFoundByUsers%' => $session->getWordFoundByUsers()], 'commands');
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }

    public function getRandomWord(): string
    {
        $locale = $this->translator->getLocale();
        $possibleWords = file(__DIR__.'/words.'. $locale .'.txt', FILE_IGNORE_NEW_LINES);

        return mb_strtoupper($possibleWords[random_int(0, count($possibleWords) - 1)]);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function retrieveWord(): CacheItem
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
