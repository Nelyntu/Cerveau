<?php

namespace Twitch\CommandHandler;

interface CommandHandlerInterface
{
    public function supports($name): bool;
    public function handle($args): ?string;
}
