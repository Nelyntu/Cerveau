<?php

namespace App\LiveStats;

class Row
{
    public string $chatters = '';
    public string $data = '';

    public function __construct(public readonly string $channel)
    {
    }
}
