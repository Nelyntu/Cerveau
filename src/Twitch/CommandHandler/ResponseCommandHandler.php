<?php

namespace Twitch\CommandHandler;

use Twitch\Command;
use Twitch\Twitch;

class ResponseCommandHandler implements CommandHandlerInterface
{
    /** @var string[] */
    private array $responses;

    /**
     * @param string[] $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function supports(string $name): bool
    {
        return array_key_exists($name, $this->responses);
    }

    public function handle(Command $command): ?string
    {
        return $this->responses[$command->command];
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return array_keys($this->responses);
    }
}
