<?php

namespace Twitch;

class Response
{
    public function __construct(public string $channel, public string $fromUser, public string $message)
    {
    }
}
