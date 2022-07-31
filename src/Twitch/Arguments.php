<?php

namespace Twitch;

class Arguments
{
    public static function createFrom(?string $text): self
    {
        if ($text === null) {
            return new self(null, null, null);
        }

        preg_match('/^([^ ]+)(?: +(.*))?$/', $text, $matches);

        return new self($text, $matches[1], $matches[2] ?? null);
    }

    private function __construct(public ?string $text, public ?string $firstArgument, public ?string $rest)
    {
    }
}
