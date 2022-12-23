<?php

namespace Cerveau\Bot;

class Message
{
    public function __construct(public string $channel, public string $user, public string $text)
    {
    }
}
