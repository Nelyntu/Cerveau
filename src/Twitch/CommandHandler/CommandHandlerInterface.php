<?php

namespace Twitch\CommandHandler;

use Twitch\Command;

interface CommandHandlerInterface
{
    public function supports(string $name): bool;

    public function handle(Command $command): ?string;

    public function isAuthorized(string $username): bool;

    /**
     * @return string[]
     */
    public function getName(): array;
}
