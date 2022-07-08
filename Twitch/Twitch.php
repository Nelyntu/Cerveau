<?php

/**
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

use Exception;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use Twitch\CommandHandler\CommandHandlerInterface;

class Twitch
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
    protected LoopInterface $loop;
    protected CommandDispatcher $commands;
    private string $secret;
    private string $nick;
    /** @var string[] */
    private array $initialChannels;
    /** @var string[] */
    private array $badWords = [];
    protected Connector $connector;
    protected ?ConnectionInterface $connection = null;
    protected bool $running = false;
    private bool $closing = false;
    private int $logLevel;
    private ?IRCApi $ircApi = null;

    public function __construct(array $options = [])
    {
        if (PHP_SAPI !== 'cli') {
            trigger_error(
                'TwitchPHP will not run on a webserver. Please use PHP CLI to run a TwitchPHP self-bot.',
                E_USER_ERROR);
        }

        $options = $this->resolveOptions($options);

        $this->loop = $options['loop'];
        $this->secret = $options['secret'];
        $this->nick = $options['nick'];
        $this->initialChannels = array_map('strtolower', $options['channels']);
        if (empty($this->initialChannels)) {
            $this->initialChannels = [$options['nick']];
        }

        $this->logLevel = $options['logLevel'];

        $this->connector = new Connector($this->loop, $options['socket_options']);

        if (is_array($options['badwords'])) {
            $this->badWords = $options['badwords'];
        }
        $this->commands = new CommandDispatcher($this, $options['commandsymbol'] ?? ['!'], $this->logLevel);
    }

    public function run(bool $runLoop = true): void
    {
        $this->emit('[RUN]', self::LOG_INFO);
        if (!$this->running) {
            $this->running = true;
            $this->connect();
        }
        $this->emit('[LOOP->RUN]', self::LOG_INFO);
        if ($runLoop) {
            $this->loop->run();
        }
    }

    public function close(bool $closeLoop = true): void
    {
        $this->emit('[CLOSE]', self::LOG_INFO);
        if ($this->running) {
            $this->running = false;
            $this->ircApi->leaveChannels();
        }
        if ($closeLoop && !$this->closing) {
            $this->closing = true;
            $this->emit('[LOOP->STOP]', self::LOG_INFO);

            $this->loop->addTimer(3, function () {
                $this->closing = false;
                $this->loop->stop();
            });
        }
    }

    /**
     * Attempt to catch errors with the user-provided $options early
     */
    protected function resolveOptions(array $options = []): array
    {
        if (!$options['secret']) {
            trigger_error(
                'TwitchPHP requires a client secret to connect. Get your Chat OAuth Password here => https://twitchapps.com/tmi/',
                E_USER_ERROR);
        }
        if (!$options['nick']) {
            trigger_error(
                'TwitchPHP requires a client username to connect. This should be the same username you use to log in.',
                E_USER_ERROR);
        }
        $options['nick'] = strtolower($options['nick']);
        $options['loop'] = $options['loop'] ?? Loop::get();
        $options['symbol'] = $options['symbol'] ?? '!';
        $options['responses'] = $options['responses'] ?? [];
        $options['functions'] = $options['functions'] ?? [];

        return $options;
    }

    /**
     * Connect the bot to Twitch
     * This command should not be run while the bot is still connected to Twitch
     * Additional handling may be needed in the case of disconnect via $connection->on('close' (See: Issue #1 on GitHub)
     */
    protected function connect(): void
    {
        $url = 'irc.chat.twitch.tv';
        $port = '6667';
        $this->emit("[CONNECT] $url:$port", self::LOG_INFO);

        if ($this->connection) {
            $this->emit('[SYMANTICS ERROR] A connection already exists!', self::LOG_ERROR);

            return;
        }

        $this->connector->connect("$url:$port")->then(
            function (ConnectionInterface $connection) {
                $this->ircApi = new IRCApi($connection, $this);
                $this->ircApi->init($this->secret, $this->nick, $this->initialChannels);

                $connection->on('data', function ($data) {
                    $this->process($data);
                });
                $connection->on('close', function () {
                    $this->emit('[CLOSE]', Twitch::LOG_NOTICE);
                });
                $this->emit('[CONNECTED]', Twitch::LOG_NOTICE);
            },
            function (Exception $exception) {
                $this->emit('[ERROR] ' . $exception->getMessage(), Twitch::LOG_ERROR);
            }
        );
    }

    protected function process(string $data): void
    {
        $this->emit('DATA' . $data . '`', self::LOG_DEBUG);
        if (trim($data) === "PING :tmi.twitch.tv") {
            $this->ircApi->pingPong();

            return;
        }
        if (false !== strpos($data, 'PRIVMSG')) {
            $response = $this->parseMessage($data);
            if ($response === null) {
                return;
            }

            // why this code ?
            // does it ban someone that the bot says bad words ?
            if ($this->badWordsCheck($response->message)) {
                $this->ircApi->ban($response->fromUser);
            }
            $payload = '@' . $response->fromUser . ', ' . $response->message . "\n";
            $this->ircApi->sendMessage($payload, $response->channel);
        }
    }

    protected function badWordsCheck($message): bool
    {
        if (empty($this->badWords)) {
            return false;
        }
        $this->emit('[BADWORD CHECK] ' . $message, self::LOG_DEBUG);
        foreach ($this->badWords as $badWord) {
            if (strpos($message, $badWord) !== false) {
                $this->emit('[BADWORD FOUND] ' . $badWord, self::LOG_INFO);

                return true;
            }
        }

        return false;
    }

    protected function parseMessage(string $data): ?Response
    {
        $message = ChatMessageParser::parse($data);

        $this->emit(
            '[PRIVMSG] (#' . $message->channel . ') ' . $message->user . ': ' . $message->text,
            self::LOG_DEBUG);

        if ($this->badWordsCheck($message->text)) {
            $this->ircApi->ban($message->user);
        }

        $response = $this->commands->handle($message);

        if (!$response) {
            return null;
        }

        return new Response($message->channel, $message->user, $response);
    }

    /**
     * This function can double as an event listener
     */
    public function emit(string $string, $level): void
    {
        if ($level > $this->logLevel) {
            return;
        }
        echo "[EMIT][" . date('H:i:s') . "][" . self::LOG_LEVEL_LABELS[$level] . "] " . $string . PHP_EOL;
    }

    public function getCommandSymbols(): array
    {
        return $this->commands->getCommandSymbols();
    }

    public function addCommand(CommandHandlerInterface $command): void
    {
        $this->commands->addCommand($command);
    }

    public function getIrcApi(): ?IRCApi
    {
        return $this->ircApi;
    }

    public function getCommands(): CommandDispatcher
    {
        return $this->commands;
    }
}
