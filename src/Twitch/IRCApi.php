<?php

namespace Twitch;

use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;

class IRCApi
{
    private ConnectionInterface $connection;
    /** @var string[] */
    private array $channels = [];
    private string $nick;
    private LoggerInterface $logger;
    private ConnectorInterface $connector;
    private string $serverAddress;

    public function __construct($serverAddress, ConnectorInterface $connector, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->connector = $connector;
        $this->serverAddress = $serverAddress;
    }

    public function connect(): PromiseInterface
    {
        $this->logger->info("[IRC][CONNECT] $this->serverAddress");
        return $this->connector->connect($this->serverAddress)
            ->then(fn(ConnectionInterface $connection) => $this->connection = $connection);
    }

    public function init($secret, $nick, array $channels): void
    {
        $this->logger->info('[IRC][INIT]');
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
        $this->logger->notice('[IRC][REPLY] #' . $channel . ' - ' . $data);
        if (!isset($this->connection)) {
            return;
        }

        $this->connection->write('PRIVMSG #' . $channel . " :" . $data . "\n");
    }

    public function joinChannel(string $string): void
    {
        $this->logger->info('[IRC][JOIN] `' . $string . '`');
        if (!isset($this->connection)) {
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
        $this->logger->info('[IRC][LEAVE] `' . $channelToLeave . '`');
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
        $this->logger->info('[IRC][BAN] ' . $username . ' - ' . $reason,);
        if ($username === $this->nick || in_array($username, $this->channels, true)) {
            return;
        }
        $this->connection->write("/ban $username $reason");
    }

    public function pong(): void
    {
        $this->logger->debug("[IRC][PONG] :tmi.twitch.tv");
        $this->connection->write("PONG :tmi.twitch.tv\n");
    }

    public function leaveChannels(): void
    {
        foreach ($this->channels as $channel) {
            $this->leaveChannel($channel);
        }
    }
}
