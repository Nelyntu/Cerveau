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

### Installing Cerveau

Cerveau is installed cloning the project :

```
git clone https://github.com/Nelyntu/Cerveau.git
```

Then run `composer install`.

## Contribution

Run once :

```
composer install --dev
```

Commands to run :
```
vendor/bin/phpstan analyse src --level 9
vendor/bin/rector process src
```

## Original project

You should know it's a fork from [VZGCoders/TwitchPHP](https://github.com/VZGCoders/TwitchPHP).
Original copyrights are from [ValZarGaming](mailto:valzargaming@gmail.com).
