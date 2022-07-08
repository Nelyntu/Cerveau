<?php

namespace Nelyntu;

class Logger
{
    public const LOG_ERROR = -1;
    public const LOG_NOTICE = 1;
    public const LOG_INFO = 2;
    public const LOG_DEBUG = 3;
    private const LOG_LEVEL_LABELS = [
        self::LOG_ERROR => 'ERROR',
        self::LOG_NOTICE => 'NOTICE',
        self::LOG_INFO => 'INFO',
        self::LOG_DEBUG => 'DEBUG',
    ];

    private int $logLevel;

    public function __construct(int $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    public function log(string $string, $level): void
    {
        if ($level > $this->logLevel) {
            return;
        }
        echo "[EMIT][" . date('H:i:s') . "][" . self::LOG_LEVEL_LABELS[$level] . "] " . $string . PHP_EOL;
    }
}
