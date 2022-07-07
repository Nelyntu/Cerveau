<?php

namespace Twitch;

class Response
{
    public string $fromUser;
    public string $channel;
    public string $message;

    public function __construct(string $channel, string $fromUser, string $message)
    {
        $this->channel = $channel;
        $this->message = $message;
        $this->fromUser = $fromUser;
    }
}
