<?php

namespace Twitch\CommandHandler;

use Twitch\Command;

interface CommandHandlerInterface
{
    public function supports($name): bool;

    public function handle(Command $command): ?string;
}
