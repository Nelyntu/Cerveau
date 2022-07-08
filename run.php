<?php

/**
 * Original development by ValZarGaming <valzargaming@gmail.com>
 */

use React\EventLoop\Loop;
use React\Socket\Connector;
use Twitch\IRCApi;

require 'vendor/autoload.php';

$options = require __DIR__ . '/settings.php';

$logger = new Nelyntu\Logger($options['logLevel']);

$loop = Loop::get();
$connector = new Connector($loop, $options['socket_options']);
$ircApi = new IRCApi('irc.chat.twitch.tv:6667', $connector, $logger);

$userList = new Twitch\UserList($options['nick'], $options['whitelist']);
$commandDispatcher = new Twitch\CommandDispatcher($options['commandsymbol'] ?? ['!'], $logger);
$twitch = new Twitch\Twitch($ircApi, $logger, $options, $commandDispatcher, $loop);

$commandDispatcher->addCommand(new Twitch\CommandHandler\BanCommandHandlerHandler($ircApi, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\HelpCommandHandler($commandDispatcher));
$commandDispatcher->addCommand(new Twitch\CommandHandler\JoinCommandHandler($ircApi, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\LeaveCommandHandler($ircApi, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\PhpCommandHandler($userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\SoCommandHandler($userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\StopCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\ResponseCommandHandler($options['responses']));

$twitch->run();
