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
	private $channels;
    /** @var string[] */
    private $commandSymbols;
    /** @var string[] */
	private array $badwords;
    /** @var string[] */
	private $whitelist;
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
	
	private ?string $reallastuser = null;
	private ?string $reallastchannel = null;
	private ?string $lastmessage = null;
	private ?string $lastuser = null; //Used a command
//	private $lastchannel; //Where command was used
    private bool $closing = false;
    private int $logLevel;

    public function __construct(array $options = [])
	{
		if (php_sapi_name() !== 'cli') trigger_error('TwitchPHP will not run on a webserver. Please use PHP CLI to run a TwitchPHP self-bot.', E_USER_ERROR);
		
		$options = $this->resolveOptions($options);
		
		$this->loop = $options['loop'];
		$this->secret = $options['secret'];
		$this->nick = $options['nick'];
		foreach($options['channels'] as $channel) $this->channels[] = strtolower($channel);
		if(is_null($this->channels)) $this->channels = array($options['nick']);
        $this->commandSymbols = $options['commandsymbol'] ?? array('!');
		
		foreach ($options['whitelist'] as $whitelist) $this->whitelist[] = $whitelist;
		$this->responses = $options['responses'] ?? array();
		$this->functions = $options['functions'] ?? array();
        $this->restrictedFunctions = $options['restricted_functions'] ?? array();
        $this->privateFunctions = $options['private_functions'] ?? array();

//		$this->socket_options = $options['socket_options'];

        $this->logLevel = $options['logLevel'];

		$this->connector = new Connector($this->loop, $options['socket_options']);

		if (is_array($options['badwords'])) $this->badwords = $options['badwords'];
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
		if ($runLoop) $this->loop->run();
	}
	
	public function close(bool $closeLoop = true): void
	{
        $this->emit('[CLOSE]', self::LOG_INFO);
		if ($this->running) {
			$this->running = false;
			foreach ($this->channels as $channel) $this->leaveChannel($channel);
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
		if (!$options['secret']) trigger_error('TwitchPHP requires a client secret to connect. Get your Chat OAuth Password here => https://twitchapps.com/tmi/', E_USER_ERROR);
		if (!$options['nick']) trigger_error('TwitchPHP requires a client username to connect. This should be the same username you use to log in.', E_USER_ERROR);
		$options['nick'] = strtolower($options['nick']);
		$options['loop'] = $options['loop'] ?? Loop::get();
		$options['symbol'] = $options['symbol'] ?? '!';
		$options['responses'] = $options['responses'] ?? array();
		$options['functions'] = $options['functions'] ?? array();
		
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
		foreach ($this->channels as $channel) $this->joinChannel($channel);
	}

	protected function pingPong(string $data): void
	{
        $this->emit("PING :tmi.twitch.tv", self::LOG_DEBUG);
		$this->connection->write("PONG :tmi.twitch.tv\n");
        $this->emit("PONG :tmi.twitch.tv", self::LOG_DEBUG);
	}
	
	protected function process(string $data): void
	{
        $this->emit('DATA' . $data . '`', self::LOG_DEBUG);
        if (trim($data) === "PING :tmi.twitch.tv") {
			$this->pingPong($data);
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
		$this->reallastuser = $this->parseUser($data);
		$this->reallastchannel = $this->parseChannel($data);
		$this->lastmessage = trim(substr($data, strpos($data, 'PRIVMSG')+11+strlen($this->reallastchannel)));

        $this->emit("[DEBUG] [LASTMESSAGE] '" . $this->lastmessage . "'", self::LOG_DEBUG);

        $this->emit('[PRIVMSG] (#' . $this->reallastchannel . ') ' . $this->reallastuser . ': ' . $this->lastmessage, self::LOG_INFO);
		
		if (!empty($this->badwords) && $this->badwordsCheck($this->lastmessage)) {
			$this->ban($this->reallastuser);
		}
		
		$response = '';
		$commandSymbol = '';
        foreach($this->commandSymbols as $symbol) {
            if (strpos($this->lastmessage, $symbol) === 0) {
				$this->lastmessage = trim(substr($this->lastmessage, strlen($symbol)));
                $commandSymbol = $symbol;
                break;
			}
		}
        if ($commandSymbol) {
			$dataArr = explode(' ', $this->lastmessage);
			$command = strtolower(trim($dataArr[0]));
            $this->emit("[COMMAND] `$command`", self::LOG_INFO);
			$this->lastuser = $this->reallastuser;
			$this->lastchannel = $this->reallastchannel;
//			$this->lastchannel = null;
			
			//Public commands
            if (in_array($command, $this->functions, true)) {
                $this->emit('[FUNCTION]', self::LOG_INFO);
				$response = $this->commands->handle($command, $dataArr);
			}
			
			//Whitelisted commands
            if (in_array($this->lastuser, $this->whitelist, true) || $this->lastuser === $this->nick) {
                if (in_array($command, $this->restrictedFunctions, true)) {
                    $this->emit('[RESTRICTED FUNCTION]', self::LOG_INFO);
					$response = $this->commands->handle($command, $dataArr);
				}
			}
			
			//Bot owner commands (shares the same username)
            if ($this->lastuser === $this->nick && in_array($command, $this->privateFunctions, true)) {
                $this->emit('[PRIVATE FUNCTION]', self::LOG_INFO);
                $response = $this->commands->handle($command, $dataArr);
            }
			
			//Reply with a preset message
			if (isset($this->responses[$command])) {
                $this->emit('[RESPONSE]', self::LOG_INFO);
				$response = $this->responses[$command];
			}
			
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
        if (strpos($arr[0], "#") === 0) return substr($arr[0], 1);
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
}
