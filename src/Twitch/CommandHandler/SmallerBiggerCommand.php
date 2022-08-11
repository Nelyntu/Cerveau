<?php

namespace Twitch\CommandHandler;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twitch\Command;

class SmallerBiggerCommand implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'sb';

    public function __construct(private readonly FilesystemAdapter $cache, private readonly TranslatorInterface $translator)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        $suggestedValue = $command->arguments->firstArgument;
        if ($suggestedValue === null) {
            return $this->translator->trans('commands.sb.empty_choice', [], 'commands');
        }

        if (!is_numeric($suggestedValue)) {
            return $this->translator->trans('commands.sb.invalid_choice', [], 'commands');
        }

        $suggestedValue = (int)$suggestedValue;

        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:sb:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            $remainingCoolDownTime = (int)($coolDownItemCache->get() - microtime(true));

            return $this->translator->trans('commands.sb.triggered_cooldown', ['%remainingCoolDownTime%' => $remainingCoolDownTime], 'commands');
        }
        $coolDownItemCache->expiresAfter(30);
        $coolDownItemCache->set(30 + microtime(true));
        $this->cache->save($coolDownItemCache);

        // retrieve value

        $valueToFindItemCache = $this->cache->getItem('cerveau:command:sb:value');
        if (!$valueToFindItemCache->isHit()) {
            // new value !
            $valueToFind = random_int(0, 1_000);
            $valueToFindItemCache->set($valueToFind);
            $valueToFindItemCache->expiresAfter(3600 * 5);
            $this->cache->save($valueToFindItemCache);
        } else {
            $valueToFind = $valueToFindItemCache->get();
        }

        if ($suggestedValue > $valueToFind) {
            return $this->translator->trans('commands.sb.is_smaller', [], 'commands');

        }

        if ($suggestedValue < $valueToFind) {
            return $this->translator->trans('commands.sb.is_bigger', [], 'commands');
        }

        $this->cache->deleteItem('cerveau:command:sb:value');

        return $this->translator->trans('commands.sb.found', ['%valueToFind%' => $valueToFind], 'commands');
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
