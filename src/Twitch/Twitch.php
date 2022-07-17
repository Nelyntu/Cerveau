<?php

/**
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

namespace Twitch;

use Exception;
use Nelyntu\Logger;
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
    private Logger $logger;

    public function __construct(IRCApi $ircApi, Logger $logger, array $options, CommandDispatcher $commandDispatcher, LoopInterface $loop)
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
        $this->logger->log('[T][RUN]', Logger::LOG_INFO);
        if (!$this->running) {
            $this->running = true;
            $this->connect();
        }
        $this->logger->log('[T][LOOP->RUN]', Logger::LOG_INFO);
        if ($runLoop) {
            $this->loop->run();
        }
    }

    public function close(bool $closeLoop = true): void
    {
        $this->logger->log('[T][CLOSE]', Logger::LOG_INFO);
        if ($this->running) {
            $this->running = false;
            $this->ircApi->leaveChannels();
        }
        if ($closeLoop && !$this->closing) {
            $this->closing = true;
            $this->logger->log('[T][LOOP->STOP]', Logger::LOG_INFO);

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
            $this->logger->log('[T][SYMANTICS ERROR] A connection already exists!', Logger::LOG_ERROR);

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
                        $this->logger->log('[T][CLOSE]', Logger::LOG_NOTICE);
                    });
                    $this->logger->log('[T][CONNECTED]', Logger::LOG_NOTICE);
                },
                function (Exception $exception) {
                    $this->logger->log('[T][ERROR] ' . $exception->getMessage(), Logger::LOG_ERROR);
                }
            );
    }

    protected function process(string $data): void
    {
        $this->logger->log('[T]DATA: `' . $data . '`', Logger::LOG_DEBUG);
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
        $this->logger->log('[T][BADWORD CHECK] ' . $message, Logger::LOG_DEBUG);
        foreach ($this->badWords as $badWord) {
            if (strpos($message, $badWord) !== false) {
                $this->logger->log('[T][BADWORD FOUND] ' . $badWord, Logger::LOG_INFO);

                return true;
            }
        }

        return false;
    }

    protected function parseMessage(string $data): ?Response
    {
        $message = ChatMessageParser::parse($data);

        $this->logger->log(
            '[PRIVMSG] (#' . $message->channel . ') ' . $message->user . ': ' . $message->text,
            Logger::LOG_DEBUG);

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
