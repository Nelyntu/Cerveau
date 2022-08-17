<?php

namespace Twitch\CommandHandler;

use Twitch\Command;

class HeightBallCommand extends CoolDownableCommandHandler
{
    private const COMMAND_NAME = 'question';

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $coolDownCheck = $this->checkUserCoolDown($command);
        if (is_string($coolDownCheck)) {
            return $coolDownCheck;
        }

        // main code
        $possibleResponses = [
            'yes.1',
            'yes.2',
            'yes.3',
            'no.1',
            'no.2',
            'no.3',
        ];

        $responseIndex = random_int(0, count($possibleResponses) - 1);

        $transkey = $possibleResponses[$responseIndex];

        return $this->translator->trans('commands.question.' . $transkey, [], 'commands');
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }
}
