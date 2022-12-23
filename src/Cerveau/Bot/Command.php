<?php

namespace Cerveau\Bot;

class Command
{
    public function __construct(
        public string $channel,
        public string $user,
        public string $command,
        public Arguments $arguments
    ) {
    }
}
