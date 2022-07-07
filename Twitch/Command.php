<?php

namespace Twitch;

class Command
{
    public string $channel;
    public string $user;
    public string $command;
    public array $arguments;

    public function __construct(string $channel, string $user, string $command, array $arguments)
    {
        $this->channel = $channel;
        $this->user = $user;
        $this->command = $command;
        $this->arguments = $arguments;
    }
}
