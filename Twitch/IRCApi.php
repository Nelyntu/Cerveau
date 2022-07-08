<?php

namespace Twitch;

use Nelyntu\Logger;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;

class IRCApi
{
    private ConnectionInterface $connection;
    /** @var string[] */
    private array $channels = [];
    private string $nick;
    private Logger $logger;
    private ConnectorInterface $connector;
    private string $serverAddress;

    public function __construct($serverAddress, ConnectorInterface $connector, Logger $logger)
    {
        $this->logger = $logger;
        $this->connector = $connector;
        $this->serverAddress = $serverAddress;
    }

    public function connect(): PromiseInterface
    {
        $this->logger->log("[CONNECT] $this->serverAddress", Logger::LOG_INFO);
        return $this->connector->connect($this->serverAddress)
            ->then(fn(ConnectionInterface $connection) => $this->connection = $connection);
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
