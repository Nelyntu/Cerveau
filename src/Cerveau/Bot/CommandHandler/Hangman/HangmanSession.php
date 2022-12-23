<?php

namespace Cerveau\Bot\CommandHandler\Hangman;

class HangmanSession
{
    private const NOT_FOUND_CHARACTER = '❓';
    private string $wordFoundByUsers;
    /** @var string[] */
    private array $suggestedLetters = [];
    private int $fails = 0;

    public function __construct(private readonly string $wordToFind)
    {
        $this->wordFoundByUsers = str_repeat(self::NOT_FOUND_CHARACTER, strlen($wordToFind));
    }

    public function isLetterAlreadySuggested(string $letter): bool
    {
        return in_array($letter, $this->suggestedLetters, true);
    }

    public function suggestLetter(string $letter): bool
    {
        $this->suggestedLetters[] = $letter;

        $hasLetter = str_contains($this->wordToFind, $letter);

        if ($hasLetter) {
            $this->unlockLetter($letter);
        } else {
            $this->fails++;
        }

        return $hasLetter;
    }

    public function unlockLetter(string $suggestedLetter): void
    {
        $offset = 0;
        while (($position = mb_strpos($this->wordToFind, $suggestedLetter, $offset)) !== false) {
            $this->wordFoundByUsers = mb_substr_replace($this->wordFoundByUsers, $suggestedLetter, $position);
            $offset++;
        }
    }

    public function isWordFound(): bool
    {
        return !str_contains($this->wordFoundByUsers, self::NOT_FOUND_CHARACTER);
    }

    public function getFails(): int
    {
        return $this->fails;
    }

    public function getWordFoundByUsers(): string
    {
        return $this->wordFoundByUsers;
    }

    public function getWordToFind(): string
    {
        return $this->wordToFind;
    }
}
