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

	protected LoopInterface $loop;
	protected Commands $commands;

//	private $socket_options;
	
	private string $secret;
	private string $nick;
    /** @var string[] */
	private $channels;
    /** @var string[] */
	private $commandsymbol;
    /** @var string[] */
	private array $badwords;
    /** @var string[] */
	private $whitelist;
    /** @var string[] */
	private array $responses;
    /** @var string[] */
	private array $functions;
    /** @var string[] */
	private array $restricted_functions;
    /** @var string[] */
	private array $private_functions;
	
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
		$this->commandsymbol = $options['commandsymbol'] ?? array('!');
		
		foreach ($options['whitelist'] as $whitelist) $this->whitelist[] = $whitelist;
		$this->responses = $options['responses'] ?? array();
		$this->functions = $options['functions'] ?? array();
		$this->restricted_functions	= $options['restricted_functions'] ?? array();
		$this->private_functions = $options['private_functions'] ?? array();

//		$this->socket_options = $options['socket_options'];

        $this->logLevel = $options['logLevel'];

		$this->connector = new Connector($this->loop, $options['socket_options']);

		if (is_array($options['badwords'])) $this->badwords = $options['badwords'];
		$this->commands = $options['commands'] ?? new Commands($this, $this->logLevel);
	}
	
	public function run(bool $runLoop = true): void
	{
		$this->emit('[RUN]', Twitch::LOG_INFO);
		if (!$this->running) {
			$this->running = true;
			$this->connect();
		}
		$this->emit('[LOOP->RUN]', Twitch::LOG_INFO);
		if ($runLoop) $this->loop->run();
	}
	
	public function close(bool $closeLoop = true): void
	{
		$this->emit('[CLOSE]', Twitch::LOG_INFO);
		if ($this->running) {
			$this->running = false;
			foreach ($this->channels as $channel) $this->leaveChannel($channel);
		}
		if ($closeLoop) {
			if(!$this->closing) {
				$this->closing = true;
				$this->emit('[LOOP->STOP]', Twitch::LOG_INFO);

				$this->loop->addTimer(3, function () {
                    $this->closing = false;
                    $this->loop->stop();
				});
			}
		}
	}
	
	public function sendMessage(string $data, ?string $channel = null): void
	{
		if (!isset($this->connection)) {
            return;
        }

        $this->connection->write("PRIVMSG #" . ($channel ?? $this->reallastchannel ?? current($this->channels)) . " :" . $data . "\n");
        $this->emit('[REPLY] #' . ($channel ?? $this->reallastchannel ?? current($this->channels)) . ' - ' . $data, Twitch::LOG_NOTICE);
        // if ($channel)
        $this->reallastchannel = $channel ?? $this->reallastchannel ?? current($this->channels);
	}
	
	public function joinChannel(string $string = ""): void
	{
		$this->emit('[VERBOSE] [JOIN CHANNEL] `' . $string . '`', Twitch::LOG_INFO);
		if (!isset($this->connection) || !$string) {
            return;
        }

        $string = strtolower($string);
        $this->connection->write("JOIN #" . $string . "\n");
        if (!in_array($string, $this->channels)) $this->channels[] = $string;
	}
	
	/*
	* Commands.php should never send a string so as to prevent users from being able to tell the bot to leave someone else's channel
	* This command is exposed so other ReactPHP applications can call it, but those applications should always attempt to pass a valid string
	* getChannels has also been exposed for the purpose of checking if the string exists before attempting to call this function
	*/
	public function leaveChannel(?string $string = ""): void
	{
		$this->emit('[VERBOSE] [LEAVE CHANNEL] `' . $string . '`', Twitch::LOG_INFO);
		if (isset($this->connection)) {
			$string = strtolower($string ?? $this->reallastchannel);
			$this->connection->write("PART #" . ($string ?? $this->reallastchannel) . "\n");
			foreach ($this->channels as &$channel) {
				if ($channel == $string) $channel = null;
				unset ($channel);
			}
		}
	}
	
	public function ban($username, $reason = ''): bool
	{
		$this->emit('[BAN] ' . $username . ' - ' . $reason, Twitch::LOG_INFO);
		if ( ($username != $this->nick) && (!in_array($username, $this->channels)) ) {
			$this->connection->write("/ban $username $reason");
			return true;
		}
		return false;
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
		$this->emit("[CONNECT] $url:$port", Twitch::LOG_INFO);

        if ($this->connection) {
            $this->emit('[SYMANTICS ERROR] A connection already exists!', Twitch::LOG_ERROR);
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
                    $this->emit('[CLOSE]');
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
		$this->emit('[INIT IRC]', Twitch::LOG_INFO);
		$this->connection->write("PASS " . $this->secret . "\n");
		$this->connection->write("NICK " . $this->nick . "\n");
		$this->connection->write("CAP REQ :twitch.tv/membership\n");
		foreach ($this->channels as $channel) $this->joinChannel($channel);
	}

	protected function pingPong(string $data): void
	{
		$this->emit("[DEBUG] [" . date('h:i:s') . "] PING :tmi.twitch.tv", Twitch::LOG_DEBUG);
		$this->connection->write("PONG :tmi.twitch.tv\n");
		$this->emit("[DEBUG] [" . date('h:i:s') . "] PONG :tmi.twitch.tv", Twitch::LOG_DEBUG);
	}
	
	protected function process(string $data): void
	{
		$this->emit("[DEBUG] [DATA] " . $data, Twitch::LOG_DEBUG);
		if (trim($data) == "PING :tmi.twitch.tv") {
			$this->pingPong($data);
			return;
		}
		if (preg_match('/PRIVMSG/', $data)) {
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
		$this->emit('[BADWORD CHECK] ' . $message, Twitch::LOG_DEBUG);
		foreach ($this->badwords as $badword) {
			if (str_contains($message, $badword)) {
				$this->emit('[BADWORD] ' . $badword, Twitch::LOG_INFO);
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

        $this->emit('[DEBUG] [DATA] `' . $data . '`', Twitch::LOG_DEBUG);
        $this->emit("[DEBUG] [LASTMESSAGE] '" . $this->lastmessage . "'", Twitch::LOG_DEBUG);

		$this->emit('[PRIVMSG] (#' . $this->reallastchannel . ') ' . $this->reallastuser . ': ' . $this->lastmessage, Twitch::LOG_INFO);
		
		if (!empty($this->badwords) && $this->badwordsCheck($this->lastmessage)) {
			$this->ban($this->reallastuser);
		}
		
		$response = '';
		$commandsymbol = '';
		foreach($this->commandsymbol as $symbol) {
			if (str_starts_with($this->lastmessage, $symbol)) {
				$this->lastmessage = trim(substr($this->lastmessage, strlen($symbol)));
				$commandsymbol = $symbol;
				break 1;
			}
		}
		if ($commandsymbol) {
			$dataArr = explode(' ', $this->lastmessage);
			$command = strtolower(trim($dataArr[0]));
			$this->emit("[COMMAND] `$command`", Twitch::LOG_INFO);
			$this->lastuser = $this->reallastuser;
			$this->lastchannel = $this->reallastchannel;
//			$this->lastchannel = null;
			
			//Public commands
			if (in_array($command, $this->functions)) {
				$this->emit('[FUNCTION]', Twitch::LOG_INFO);
				$response = $this->commands->handle($command, $dataArr);
			}
			
			//Whitelisted commands
			if ( in_array($this->lastuser, $this->whitelist) || ($this->lastuser == $this->nick) ) {
				if (in_array($command, $this->restricted_functions)) {
					$this->emit('[RESTRICTED FUNCTION]', Twitch::LOG_INFO);
					$response = $this->commands->handle($command, $dataArr);
				}
			}
			
			//Bot owner commands (shares the same username)
			if ($this->lastuser == $this->nick) {
				if (in_array($command, $this->private_functions)) {
					$this->emit('[PRIVATE FUNCTION]', Twitch::LOG_INFO);
					$response = $this->commands->handle($command, $dataArr);
				}
			}
			
			//Reply with a preset message
			if (isset($this->responses[$command])) {
				$this->emit('[RESPONSE]', Twitch::LOG_INFO);
				$response = $this->responses[$command];
			}
			
		}
		return $response;
	}
	
	protected function parseUser(string $data): ?string
	{
		if (substr($data, 0, 1) == ":") {
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
		if (str_starts_with($arr[0], "#")) return substr($arr[0], 1);
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
        echo "[EMIT] $string" . PHP_EOL;
	}
	
	public function getChannels(): array
	{
		return $this->channels;
	}
	
	public function getCommandSymbol(): array
	{
		return $this->commandsymbol;
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
		return $this->restricted_functions;
	}
	
	public function getPrivateFunctions(): array
	{
		return $this->private_functions;
	}
}