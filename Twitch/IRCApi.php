<?php

namespace Twitch;

use Nelyntu\Logger;
use React\Socket\ConnectionInterface;

class IRCApi
{
    private ConnectionInterface $connection;
    /** @var string[] */
    private array $channels = [];
    private string $nick;
    private Logger $logger;

    public function __construct(ConnectionInterface $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function init($secret, $nick, array $channels): void
    {
        $this->emit('[INIT IRC]', Logger::LOG_INFO);
        $this->connection->write("PASS " . $secret . "\n");
        $this->connection->write("NICK " . $nick . "\n");
        $this->connection->write("CAP REQ :twitch.tv/membership\n");
        $this->nick = $nick;
        foreach ($channels as $channel) {
            $this->joinChannel($channel);
            $this->channels[] = $channel;
        }
    }

    public function sendMessage(string $data, string $channel): void
    {
        if (!isset($this->connection)) {
            return;
        }

        $this->connection->write('PRIVMSG #' . $channel . " :" . $data . "\n");
        $this->emit('[REPLY] #' . $channel . ' - ' . $data, Logger::LOG_NOTICE);
    }

    public function joinChannel(string $string = ""): void
    {
        $this->emit('[VERBOSE] [JOIN CHANNEL] `' . $string . '`', Logger::LOG_INFO);
        if (!isset($this->connection) || !$string) {
            return;
        }

        $string = strtolower($string);
        $this->connection->write("JOIN #" . $string . "\n");

        if (!in_array($string, $this->channels, true)) {
            $this->channels[] = $string;
        }
    }

    /**
     * This command is exposed so other ReactPHP applications can call it, but those applications should always attempt to pass a valid string
     * getChannels has also been exposed for the purpose of checking if the string exists before attempting to call this function
     */
    public function leaveChannel(string $channelToLeave): void
    {
        $channelToLeave = strtolower($channelToLeave);
        $this->emit('[VERBOSE] [LEAVE CHANNEL] `' . $channelToLeave . '`', Logger::LOG_INFO);
        if (!isset($this->connection)) {
            return;
        }

        $this->connection->write("PART #" . $channelToLeave . "\n");
        $channelKey = array_search($channelToLeave, $this->channels, true);
        if ($channelKey !== false) {
            unset($this->channels[$channelKey]);
        }
    }

    public function ban($username, $reason = ''): void
    {
        $this->emit('[BAN] ' . $username . ' - ' . $reason, Logger::LOG_INFO);
        if ($username === $this->nick || in_array($username, $this->channels, true)) {
            return;
        }
        $this->connection->write("/ban $username $reason");
    }

    public function pingPong(): void
    {
        $this->emit("PING :tmi.twitch.tv", Logger::LOG_DEBUG);
        $this->connection->write("PONG :tmi.twitch.tv\n");
        $this->emit("PONG :tmi.twitch.tv", Logger::LOG_DEBUG);
    }

    public function leaveChannels(): void
    {
        foreach ($this->channels as $channel) {
            $this->leaveChannel($channel);
        }
    }

    protected function emit($message, $logLevel): void
    {
        $this->logger->log($message, $logLevel);
    }
}
