<?php

namespace Twitch;

class Response
{
    public string $channel;
    public string $message;

    public function __construct(string $channel, string $message)
    {
        $this->channel = $channel;
        $this->message = $message;
    }
}
