<?php

namespace Twitch;

class Message
{
    public string $channel;
    public string $user;
    public string $text;

    public function __construct(string $channel, string $user, string $text)
    {
        $this->channel = $channel;
        $this->user = $user;
        $this->text = $text;
    }
}
