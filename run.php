<?php
/*
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */

require 'vendor/autoload.php';

$options = require __DIR__ . '/settings.php';

// Responses that reference other values in options should be declared afterwards
$options['responses']['social'] = 'Come follow the magick through several dimensions:  Twitter - ' . $options['social']['twitter'] . ' |  Instagram - ' . $options['social']['instagram'] . ' |  Discord - ' . $options['social']['discord'] . ' |  Tumblr - ' . $options['social']['tumblr'] . ' |  YouTube - ' . $options['social']['youtube'];
$options['responses']['tip'] = 'Wanna help fund the magick?  PayPal - ' . $options['tip']['paypal'] . ' |  CashApp - ' . $options['tip']['cashapp'];
$options['responses']['discord'] = $options['social']['discord'];

$userList = new \Twitch\UserList($options['nick'], $options['whitelist']);
$twitch = new Twitch\Twitch($options);

$twitch->addCommand(new \Twitch\CommandHandler\BanCommandHandlerHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\HelpCommandHandler($twitch));
$twitch->addCommand(new \Twitch\CommandHandler\JoinCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\LeaveCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\PhpCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\SoCommandHandler($twitch, $userList));
$twitch->addCommand(new \Twitch\CommandHandler\StopCommandHandler($twitch, $userList));

$twitch->run();
