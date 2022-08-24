<?php

namespace Cerveau;

class Message
{
    public function __construct(public string $channel, public string $user, public string $text)
    {
    }
}
