<?php

/**
 * Original development by ValZarGaming <valzargaming@gmail.com>
 */

require 'vendor/autoload.php';

$options = require __DIR__ . '/settings.php';

$logger = new Nelyntu\Logger($options['logLevel']);
$userList = new Twitch\UserList($options['nick'], $options['whitelist']);
$commandDispatcher = new Twitch\CommandDispatcher($options['commandsymbol'] ?? ['!'], $logger);
$twitch = new Twitch\Twitch($logger, $options, $commandDispatcher);

$commandDispatcher->addCommand(new Twitch\CommandHandler\BanCommandHandlerHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\HelpCommandHandler($commandDispatcher));
$commandDispatcher->addCommand(new Twitch\CommandHandler\JoinCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\LeaveCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\PhpCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\SoCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\StopCommandHandler($twitch, $userList));
$commandDispatcher->addCommand(new Twitch\CommandHandler\ResponseCommandHandler($twitch, $options['responses']));

$twitch->run();
