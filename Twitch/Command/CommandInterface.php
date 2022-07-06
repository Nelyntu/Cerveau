<?php

namespace Twitch\Command;

interface CommandInterface
{
    public function supports($name): bool;
    public function handle($args): ?string;
}
