<?php

namespace Cerveau\Bot;

class Response
{
    public function __construct(public string $channel, public string $fromUser, public string $message)
    {
    }
}
