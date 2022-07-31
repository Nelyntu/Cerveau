<?php

namespace Twitch;

class ChatMessageParser
{
    public static function parse(string $data): Message
    {
        $user = self::parseUser($data);
        $channel = self::parseChannel($data);
        $text = trim(substr($data, strpos($data, 'PRIVMSG') + 11 + strlen($channel)));

        return new Message($channel, $user, $text);
    }

    protected static function parseUser(string $data): ?string
    {
        $user = null;
        if (str_starts_with($data, ":")) {
            $tmp = explode('!', $data);
            $user = substr($tmp[0], 1);
        }

        return $user;
    }

    /**
     * For "#foo bar', it will return "foo"
     */
    protected static function parseChannel(string $data): ?string
    {
        $arr = explode(' ', substr($data, strpos($data, '#')));
        if (str_starts_with($arr[0], "#")) {
            return substr($arr[0], 1);
        }

        return null;
    }
}
