<?php

use React\Socket\ConnectionInterface;

class twitch {
	protected $loop;
	
    private $secret;
    private $nick;
	private $commandsymbol;
	private $responses;
	private $functions;
	
	protected $connector;
	protected $connection;
	protected $running;	
	protected $closing;
	
	private $channel;
	private $lastuser;
	private $lastmessage;
	

    function __construct(array $options = [])
	{
		if (php_sapi_name() !== 'cli') {
            trigger_error('TwitchPHP will not run on a webserver. Please use PHP CLI to run a TwitchPHP self-bot.', E_USER_ERROR);
        }
		
		$options = $this->resolveOptions($options);
		
		$this->loop = $options['loop'];
		$this->secret = $options['secret'];
        $this->nick = $options['nick'];
		$this->channel = strtolower($options['channel']) ?? strtolower($options['nick']);
		$this->commandsymbol = $options['commandsymbol'] ?? array('!');
        $this->responses = $options['responses'] ?? array();
        $this->functions = $options['functions'] ?? array();
		
		$this->connector = new React\Socket\Connector($this->loop, $options['socket_options']);
    }
	
	public function run(): void
	{
		if(!$this->running){
			$this->running = true;
			$this->connect();
		}
		$this->loop->run();
		return;
	}
	
	public function close(bool $closeLoop = true): void
    {
        $this->closing = true;
        
        if ($closeLoop) {
            $this->loop->stop();
        }
    }
    
    public function initIRC(ConnectionInterface $connection){
        global $secret;
        $connection->write("PASS " . $this->secret . "\n");
        $connection->write("NICK " . $this->nick . "\n");
        $connection->write("CAP REQ :twitch.tv/membership\n");
        $connection->write("JOIN #" . $this->channel . "\n");
    }
	
	public function joinChannel($string){
		$connection->write("JOIN #" . $string . "\n");
	}

    public function pingPong($data, ConnectionInterface $connection){
        echo "[" . date('h:i:s') . "] PING :tmi.twitch.tv\n";
        $connection->write("PONG :tmi.twitch.tv\n");
        echo "[" . date('h:i:s') . "] PONG :tmi.twitch.tv\n";
    }

    public function sendMessage($data, ConnectionInterface $connection){
        $connection->write("PRIVMSG #" . $this->channel . " :" . $data . "\n");
		echo "[REPLY] $data";
    }

    public function parseUser($data){
        if (substr($data, 0, 1) == ":"){
            $tmp = explode('!', $data);
			$user = substr($tmp[0], 1);
			$this->lastuser = $user;
            return $user;
        }
    }

    public function parseMessage($data){
        $messageContents = str_replace(PHP_EOL, "", preg_replace('/.* PRIVMSG.*:/', '', $data));
		echo "[PRIVMSG CONTENT] $messageContents" . PHP_EOL;
        $dataArr = explode(' ', $messageContents);
		
		$commandsymbol = '';
		foreach($this->commandsymbol as $temp){
			if (in_array(substr($messageContents, 0, strlen($temp)), $this->commandsymbol)){
				$valid = true;
				$commandsymbol = $temp;
				break 1;
			}
		}
		if($commandsymbol){
			//Commands that require us to do something
			if (isset($this->functions[$command])){
				//Do a function then check for a return
			}
			
			//Reply with a preset message
			$command = strtolower(trim(substr($dataArr[0], strlen($commandsymbol))));
			if (isset($this->responses[$command])){
				return $this->responses[$command];
			}
		}
		return;
    }

    public function scrape($data, ConnectionInterface $connection){
        if (trim($data) == "PING :tmi.twitch.tv"){
            $this->pingPong($data, $connection);
            return;
        }
		echo "[DATA] " . $data . PHP_EOL;
        if (preg_match('/PRIVMSG/', $data)){
            $response = $this->parseMessage($data);
            if ($response){
                $user = $this->parseUser($data);
                $payload = '@' . $user . ', ' . $response . "\n";
                $this->sendMessage($payload, $connection);
            }
        }
    }
	
	protected function resolveOptions(array $options = []): array
	{
		if (!$options['secret']) {
            trigger_error('TwitchPHP requires a client secret to connect. Get your Chat OAuth Password here => https://twitchapps.com/tmi/', E_USER_ERROR);
        }
		if (!$options['nick']) {
            trigger_error('TwitchPHP requires a client username to connect. This should be the same username you use to log in.', E_USER_ERROR);
        }
		
		$options['loop'] = $options['loop'] ?? React\EventLoop\Factory::create();
		$options['symbol'] = $options['symbol'] ?? '!';
        $options['responses'] = $options['responses'] ?? array();
        $options['functions'] = $options['functions'] ?? array();
		
		return $options;
	}
	
	protected function connect(){
		$twitch = $this;
		$this->connector->connect('irc.chat.twitch.tv:6667')->then(
			function (React\Socket\ConnectionInterface $connection) use ($twitch){
				$twitch->connection = $connection;
				$twitch->initIRC($twitch->connection);
				
				$connection->on('data', function($data) use ($connection, $twitch){
					$twitch->scrape($data, $twitch->connection);
				});
			},
			function (Exception $exception){
				echo $exception->getMessage() . PHP_EOL;
			}
		);
	}
}