Cerveau : Twitch Chat bot
=========================

A chat self-bot built with [tmi](https://github.com/ghostzero/tmi) for the official [Twitch TV](https://www.twitch.tv) Internet Relay Chat (IRC) interface.

It's partially developed during live streams [on Twitch](https://www.twitch.tv/nelyntu_).

## Getting Started

### Requirements

- PHP 8.1.*
- Composer

#### Recommended Extensions

- The latest PHP version.
- One of `ext-uv` (preferred), `ext-libev` or `evt-event` for a faster, and more performant event loop.
- `ext-mbstring` if handling non-english characters.

### Install

Cerveau is installed cloning the project :

```
git clone https://github.com/Nelyntu/Cerveau.git
```

Then run `composer install`.

### Configure

Settings are in `.env` file :
* `CERVEAU_STREAMER` : your streamer name
* `CERVEAU_TWITCH_OAUTH` : the oauth token (can be get from https://twitchapps.com/tmi/)
* `CERVEAU_SUPER_USERS` : your privileged users who can use some special commands, can be an empty array
* `CERVEAU_LOCALE` : bot's language (`fr` and `en` are available)

## Customization

Customization is all about custom commands.

Just create your class which implements `\Twitch\CommandHandler\CommandHandlerInterface` (take a look at [!php](src/Twitch/CommandHandler/PhpCommandHandler.php) as example)

Optionally, the bot will respond to the chat with the string returned by the `handle` method.

As alternative, you can extend `\Twitch\CommandHandler\CoolDownableCommandHandler`.

It has a `checkUserCoolDown` method to easily handle users cooldown.

When needed, add this snippet to check cooldown and answer to your users :
```php
        $coolDownCheck = $this->checkUserCoolDown($command);
        if (is_string($coolDownCheck)) {
            return $coolDownCheck;
        }
```

NB : `$coolDownCheck` is the cooldown message (see `commands.triggered_cooldown` [translations](translations))

## Contribution

Run once :

```
composer install --dev
```

Commands to run for code quality :

```
vendor/bin/phpstan analyse src --level 9
vendor/bin/rector process src
```

## Original project

You should know it's a fork from [VZGCoders/TwitchPHP](https://github.com/VZGCoders/TwitchPHP).
Original copyrights are from [ValZarGaming](mailto:valzargaming@gmail.com).
