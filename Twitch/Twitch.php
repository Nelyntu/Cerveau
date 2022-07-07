<?php

/*
* This file is a part of the TwitchPHP project.
*
* Copyright (c) 2021 ValZarGaming <valzargaming@gmail.com>
*/

namespace Twitch;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use Twitch\CommandHandler\CommandHandlerInterface;

class Twitch
{
    public const LOG_ERROR = -1;
    public const LOG_NOTICE = 1;
    public const LOG_INFO = 2;
    public const LOG_DEBUG = 3;
    private const LOG_LEVEL_LABELS = [
        self::LOG_ERROR => 'ERROR',
        self::LOG_NOTICE => 'NOTICE',
        self::LOG_INFO => 'INFO',
        self::LOG_DEBUG => 'DEBUG',
    ];

	protected LoopInterface $loop;
	protected Commands $commands;

//	private $socket_options;
	
	private string $secret;
	private string $nick;
    /** @var string[] */
	private array $channels;
    /** @var string[] */
    private $commandSymbols;
    /** @var string[] */
	private array $badwords;
    /** @var string[] */
	private array $whitelist;
    /** @var string[] */
	private array $responses;
    /** @var string[] */
	private array $functions;
    /** @var string[] */
    private array $restrictedFunctions;
    /** @var string[] */
    private array $privateFunctions;
	
	protected Connector $connector;
	protected ?ConnectionInterface $connection = null;
	protected bool $running = false;
    private ?string $reallastchannel = null;
    private ?string $lastuser = null; //Used a command
//	private $lastchannel; //Where command was used
    private bool $closing = false;
    private int $logLevel;

    public function __construct(array $options = [])
	{
        if (PHP_SAPI !== 'cli') {
            trigger_error(
                'TwitchPHP will not run on a webserver. Please use PHP CLI to run a TwitchPHP self-bot.',
                E_USER_ERROR);
        }
		
		$options = $this->resolveOptions($options);
		
		$this->loop = $options['loop'];
		$this->secret = $options['secret'];
		$this->nick = $options['nick'];
        $this->channels = array_map('strtolower', $options['channels']);
        if (is_null($this->channels)) {
            $this->channels = [$options['nick']];
        }
        $this->commandSymbols = $options['commandsymbol'] ?? ['!'];

		$this->whitelist = $options['whitelist'];
		$this->responses = $options['responses'] ?? [];
		$this->functions = $options['functions'] ?? [];
        $this->restrictedFunctions = $options['restricted_functions'] ?? [];
        $this->privateFunctions = $options['private_functions'] ?? [];

//		$this->socket_options = $options['socket_options'];

        $this->logLevel = $options['logLevel'];

		$this->connector = new Connector($this->loop, $options['socket_options']);

        if (is_array($options['badwords'])) {
            $this->badwords = $options['badwords'];
        }
		$this->commands = $options['commands'] ?? new Commands($this, $this->logLevel);
	}
	
	public function run(bool $runLoop = true): void
	{
        $this->emit('[RUN]', self::LOG_INFO);
		if (!$this->running) {
			$this->running = true;
			$this->connect();
		}
        $this->emit('[LOOP->RUN]', self::LOG_INFO);
        if ($runLoop) {
            $this->loop->run();
        }
	}
	
	public function close(bool $closeLoop = true): void
	{
        $this->emit('[CLOSE]', self::LOG_INFO);
		if ($this->running) {
			$this->running = false;
            foreach ($this->channels as $channel) {
                $this->leaveChannel($channel);
            }
		}
        if ($closeLoop && !$this->closing) {
            $this->closing = true;
            $this->emit('[LOOP->STOP]', self::LOG_INFO);

            $this->loop->addTimer(3, function () {
                $this->closing = false;
                $this->loop->stop();
            });
        }
	}
	
	public function sendMessage(string $data, ?string $channel = null): void
	{
		if (!isset($this->connection)) {
            return;
        }

        $this->connection->write("PRIVMSG #" . ($channel ?? $this->reallastchannel ?? current($this->channels)) . " :" . $data . "\n");
        $this->emit('[REPLY] #' . ($channel ?? $this->reallastchannel ?? current($this->channels)) . ' - ' . $data, self::LOG_NOTICE);
        // if ($channel)
        $this->reallastchannel = $channel ?? $this->reallastchannel ?? current($this->channels);
	}
	
	public function joinChannel(string $string = ""): void
	{
        $this->emit('[VERBOSE] [JOIN CHANNEL] `' . $string . '`', self::LOG_INFO);
		if (!isset($this->connection) || !$string) {
            return;
        }

        $string = strtolower($string);
        $this->connection->write("JOIN #" . $string . "\n");
        if (!in_array($string, $this->channels, true)) {
            $this->channels[] = $string;
        }
	}
	
	/*
	* Commands.php should never send a string so as to prevent users from being able to tell the bot to leave someone else's channel
	* This command is exposed so other ReactPHP applications can call it, but those applications should always attempt to pass a valid string
	* getChannels has also been exposed for the purpose of checking if the string exists before attempting to call this function
	*/
	public function leaveChannel(?string $string = ""): void
	{
        $this->emit('[VERBOSE] [LEAVE CHANNEL] `' . $string . '`', self::LOG_INFO);
		if (isset($this->connection)) {
			$string = strtolower($string ?? $this->reallastchannel);
			$this->connection->write("PART #" . ($string ?? $this->reallastchannel) . "\n");
			foreach ($this->channels as &$channel) {
                if ($channel === $string) {
                    $channel = null;
                    unset ($channel);
                }
			}
		}
	}
	
    public function ban($username, $reason = ''): void
    {
        $this->emit('[BAN] ' . $username . ' - ' . $reason, self::LOG_INFO);
        if ($username === $this->nick || in_array($username, $this->channels, true)) {
            return;
        }
        $this->connection->write("/ban $username $reason");
    }

	/*
	* Attempt to catch errors with the user-provided $options early
	*/
	protected function resolveOptions(array $options = []): array
	{
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
		$options['loop'] = $options['loop'] ?? Loop::get();
		$options['symbol'] = $options['symbol'] ?? '!';
		$options['responses'] = $options['responses'] ?? [];
		$options['functions'] = $options['functions'] ?? [];

		return $options;
	}

	/*
	* Connect the bot to Twitch
	* This command should not be run while the bot is still connected to Twitch
	* Additional handling may be needed in the case of disconnect via $connection->on('close' (See: Issue #1 on GitHub)
	*/ 
	protected function connect(): void
	{
		$url = 'irc.chat.twitch.tv';
		$port = '6667';
        $this->emit("[CONNECT] $url:$port", self::LOG_INFO);

        if ($this->connection) {
            $this->emit('[SYMANTICS ERROR] A connection already exists!', self::LOG_ERROR);
            return;
        }

        $this->connector->connect("$url:$port")->then(
            function (ConnectionInterface $connection) {
                $this->connection = $connection;
                $this->initIRC();

                $connection->on('data', function($data) {
                    $this->process($data);
                });
                $connection->on('close', function () {
                    $this->emit('[CLOSE]', Twitch::LOG_NOTICE);
                });
                $this->emit('[CONNECTED]', Twitch::LOG_NOTICE);
            },
            function (\Exception $exception) {
                $this->emit('[ERROR] ' . $exception->getMessage(), Twitch::LOG_ERROR);
            }
        );
	}

    protected function initIRC(): void
	{
        $this->emit('[INIT IRC]', self::LOG_INFO);
		$this->connection->write("PASS " . $this->secret . "\n");
		$this->connection->write("NICK " . $this->nick . "\n");
		$this->connection->write("CAP REQ :twitch.tv/membership\n");
        foreach ($this->channels as $channel) {
            $this->joinChannel($channel);
        }
	}

	protected function pingPong(): void
	{
        $this->emit("PING :tmi.twitch.tv", self::LOG_DEBUG);
		$this->connection->write("PONG :tmi.twitch.tv\n");
        $this->emit("PONG :tmi.twitch.tv", self::LOG_DEBUG);
	}
	
	protected function process(string $data): void
	{
        $this->emit('DATA' . $data . '`', self::LOG_DEBUG);
        if (trim($data) === "PING :tmi.twitch.tv") {
			$this->pingPong();
			return;
		}
        if (false !== strpos($data, 'PRIVMSG')) {
			$response = $this->parseMessage($data);
			if ($response) {
				if (!empty($this->badwords) && $this->badwordsCheck($response)) {
					$this->ban($this->lastuser);
				}
				$payload = '@' . $this->lastuser . ', ' . $response . "\n";
				$this->sendMessage($payload);
			}
		}
	}
	
	protected function badwordsCheck($message): bool
	{
        $this->emit('[BADWORD CHECK] ' . $message, self::LOG_DEBUG);
		foreach ($this->badwords as $badword) {
            if (strpos($message, $badword) !== false) {
                $this->emit('[BADWORD] ' . $badword, self::LOG_INFO);
				return true;
			}
		}
		return false;
	}
	
	protected function parseMessage(string $data): ?string
	{
        $message = $this->toMessageObject($data);

        $this->reallastchannel = $message->channel;

        $this->emit('[PRIVMSG] (#' . $message->channel . ') ' . $message->user . ': ' . $message->text, self::LOG_DEBUG);

        if (!empty($this->badwords) && $this->badwordsCheck($message->text)) {
            $this->ban($message->user);
        }

        $commandSymbol = null;
        foreach($this->commandSymbols as $symbol) {
            if (strpos($message->text, $symbol) === 0) {
                $commandSymbol = $symbol;
                break;
            }
        }

        if ($commandSymbol === null) {
            return null;
        }

        $response = '';
        $lastmessage = trim(substr($message->text, strlen($commandSymbol)));
        $dataArr = explode(' ', $lastmessage);
        $command = strtolower(trim($dataArr[0]));
        $this->emit("[COMMAND] `$command`", self::LOG_INFO);
        $this->lastuser = $message->user;

        //Public commands
        if (in_array($command, $this->functions, true)) {
            $this->emit('[FUNCTION]', self::LOG_INFO);
            $response = $this->commands->handle($command, $dataArr);
        }

        //Whitelisted commands
        if (in_array($message->user, $this->whitelist, true) || $message->user === $this->nick) {
            if (in_array($command, $this->restrictedFunctions, true)) {
                $this->emit('[RESTRICTED FUNCTION]', self::LOG_INFO);
                $response = $this->commands->handle($command, $dataArr);
            }
        }

        //Bot owner commands (shares the same username)
        if ($message->user === $this->nick && in_array($command, $this->privateFunctions, true)) {
            $this->emit('[PRIVATE FUNCTION]', self::LOG_INFO);
            $response = $this->commands->handle($command, $dataArr);
        }

        //Reply with a preset message
        if (isset($this->responses[$command])) {
            $this->emit('[RESPONSE]', self::LOG_INFO);
            $response = $this->responses[$command];
        }

		return $response;
	}
	
	protected function parseUser(string $data): ?string
	{
        if (strpos($data, ":") === 0) {
			$tmp = explode('!', $data);
			$user = substr($tmp[0], 1);
		}
		return $user;
	}

    /**
     * For "#foo bar', it will return "foo"
     *
     * @param string $data
     * @return string|null
     */
	protected function parseChannel(string $data): ?string
	{
		$arr = explode(' ', substr($data, strpos($data, '#')));
        if (strpos($arr[0], "#") === 0) {
            return substr($arr[0], 1);
        }
        return null;
	}

	/*
	* This function can double as an event listener
	*/
	public function emit(string $string, $level): void
	{
        if ($level > $this->logLevel) {
            return;
        }
        echo "[EMIT][".date('H:i:s')."][".self::LOG_LEVEL_LABELS[$level]."] ". $string . PHP_EOL;
	}
	
	public function getChannels(): array
	{
		return $this->channels;
	}

    public function getCommandSymbols(): array
    {
        return $this->commandSymbols;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getRestrictedFunctions(): array
    {
        return $this->restrictedFunctions;
    }

    public function getPrivateFunctions(): array
    {
        return $this->privateFunctions;
    }

    public function addCommand(CommandHandlerInterface $command): void
    {
        $this->commands->addCommand($command);
    }

    private function toMessageObject(string $data): Message
    {
        $user = $this->parseUser($data);
        $channel = $this->parseChannel($data);
        $text = trim(substr($data, strpos($data, 'PRIVMSG') + 11 + strlen($channel)));

        return new Message($channel, $user, $text);
    }
}
