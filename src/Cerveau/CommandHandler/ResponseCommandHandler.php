<?php

namespace Cerveau\CommandHandler;

use Cerveau\Command;
use Cerveau\Bot;

class ResponseCommandHandler implements CommandHandlerInterface
{
    /**
     * @param string[] $responses
     */
    public function __construct(private readonly array $responses)
    {
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
