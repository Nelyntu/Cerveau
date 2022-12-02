<?php

namespace Cerveau\Statistics;

class LiveStat
{
    public function __construct(public readonly int $chatterCount, public readonly int $botCount)
    {
    }
}
