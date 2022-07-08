<?php
/**
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

require 'vendor/autoload.php';

$options = require __DIR__ . '/settings.php';

$logger = new \Nelyntu\Logger($options['logLevel']);
$userList = new \Twitch\UserList($options['nick'], $options['whitelist']);
$twitch = new Twitch\Twitch($logger, $options);

$twitch->addCommand(new \Twitch\CommandHandler\BanCommandHandlerHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\HelpCommandHandler($twitch));
$twitch->addCommand(new \Twitch\CommandHandler\JoinCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\LeaveCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\PhpCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\SoCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\StopCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\ResponseCommandHandler($twitch, $options['responses']));

$twitch->run();
