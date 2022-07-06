<?php
/*
 * This file is a part of the TwitchPHP project.
 *
 * Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
 */
 
require 'vendor/autoload.php';
require 'Twitch/Twitch.php';

$options = require __DIR__.'/settings.php';

// Responses that reference other values in options should be declared afterwards
$options['responses']['social'] = 'Come follow the magick through several dimensions:  Twitter - '.$options['social']['twitter'].' |  Instagram - '.$options['social']['instagram'].' |  Discord - '.$options['social']['discord'].' |  Tumblr - '.$options['social']['tumblr'].' |  YouTube - '.$options['social']['youtube'];
$options['responses']['tip'] = 'Wanna help fund the magick?  PayPal - '.$options['tip']['paypal'].' |  CashApp - '.$options['tip']['cashapp'];
$options['responses']['discord'] = $options['social']['discord'];

//include 'commands.php';
//$options['commands'] => $commands; // Import your own Twitch/Commands object to add additional functions

$twitch = new Twitch\Twitch($options);

$twitch->addCommand(new \Twitch\Command\BanCommand($twitch));
$twitch->addCommand(new \Twitch\Command\HelpCommand($twitch));
$twitch->addCommand(new \Twitch\Command\JoinCommand($twitch));
$twitch->addCommand(new \Twitch\Command\LeaveCommand($twitch));
$twitch->addCommand(new \Twitch\Command\PhpCommand($twitch));
$twitch->addCommand(new \Twitch\Command\SoCommand($twitch));
$twitch->addCommand(new \Twitch\Command\StopCommand($twitch));

$twitch->run();
