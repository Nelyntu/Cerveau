<?php

/**
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class Twitch
{
    protected LoopInterface $loop;
    protected CommandDispatcher $commands;
    private string $secret;
    private string $nick;
    /** @var string[] */
    private array $initialChannels;
    /** @var string[] */
    private array $badWords = [];
    protected ?ConnectionInterface $connection = null;
    protected bool $running = false;
    private bool $closing = false;
    private IRCApi $ircApi;
    private LoggerInterface $logger;

    public function __construct(IRCApi $ircApi, LoggerInterface $logger, array $options, CommandDispatcher $commandDispatcher, LoopInterface $loop)
    {
        if (PHP_SAPI !== 'cli') {
            trigger_error(
                'TwitchPHP will not run on a webserver. Please use PHP CLI to run a TwitchPHP self-bot.',
                E_USER_ERROR);
        }
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

        $this->loop = $loop;
        $this->secret = $options['secret'];
        $this->nick = $options['nick'];
        $this->initialChannels = array_map('strtolower', $options['channels']);
        if (empty($this->initialChannels)) {
            $this->initialChannels = [$options['nick']];
        }

        if (is_array($options['badwords'])) {
            $this->badWords = $options['badwords'];
        }
        $this->commands = $commandDispatcher;
        $this->logger = $logger;
        $this->ircApi = $ircApi;
    }

    public function run(bool $runLoop = true): void
    {
        $this->logger->info('[T][RUN]');
        if (!$this->running) {
            $this->running = true;
            $this->connect();
        }
        $this->logger->info('[T][LOOP->RUN]');
        if ($runLoop) {
            $this->loop->run();
        }
    }

    public function close(bool $closeLoop = true): void
    {
        $this->logger->info('[T][CLOSE]');
        if ($this->running) {
            $this->running = false;
            $this->ircApi->leaveChannels();
        }
        if ($closeLoop && !$this->closing) {
            $this->closing = true;
            $this->logger->info('[T][LOOP->STOP]');

            $this->loop->addTimer(3, function () {
                $this->closing = false;
                $this->loop->stop();
            });
        }
    }

    /**
     * Connect the bot to Twitch
     * This command should not be run while the bot is still connected to Twitch
     * Additional handling may be needed in the case of disconnect via $connection->on('close' (See: Issue #1 on GitHub)
     */
    protected function connect(): void
    {
        if ($this->connection) {
            $this->logger->error('[T][SYMANTICS ERROR] A connection already exists!');

            return;
        }

        $this->ircApi->connect()
            ->then(
                function (ConnectionInterface $connection) {
                    $this->ircApi->init($this->secret, $this->nick, $this->initialChannels);
                    $connection->on('data', function ($data) {
                        $this->process($data);
                    });
                    $connection->on('close', function () {
                        $this->logger->info('[T][CLOSE]');
                    });
                    $this->logger->info('[T][CONNECTED]');
                },
                function (Exception $exception) {
                    $this->logger->error('[T][ERROR] ' . $exception->getMessage());
                }
            );
    }

    protected function process(string $data): void
    {
        $this->logger->debug('[T]DATA: `' . $data . '`');
        if (trim($data) === "PING :tmi.twitch.tv") {
            $this->ircApi->pong();

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
        $this->logger->debug('[T][BADWORD CHECK] ' . $message);
        foreach ($this->badWords as $badWord) {
            if (strpos($message, $badWord) !== false) {
                $this->logger->info('[T][BADWORD FOUND] ' . $badWord);

                return true;
            }
        }

        return false;
    }

    protected function parseMessage(string $data): ?Response
    {
        $message = ChatMessageParser::parse($data);

        $this->logger->debug('[PRIVMSG] (#' . $message->channel . ') ' . $message->user . ': ' . $message->text);

        if ($this->badWordsCheck($message->text)) {
            $this->ircApi->ban($message->user);
        }

        $response = $this->commands->handle($message);

        if (!$response) {
            return null;
        }

        return new Response($message->channel, $message->user, $response);
    }
}
