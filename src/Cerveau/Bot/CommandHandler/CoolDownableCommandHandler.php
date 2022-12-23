<?php

namespace Cerveau\Bot\CommandHandler;

use Cerveau\Bot\Command;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class CoolDownableCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected readonly FilesystemAdapter $cache,
        protected readonly TranslatorInterface $translator
    ) {
    }

    protected function checkUserCoolDown(Command $command): ?string
    {
        $coolDownItemCacheKey = 'cerveau:command:' . $command->channel . ':' . $command->command . ':cooldown:' . $command->user;
        $coolDownItemCache = $this->cache->getItem($coolDownItemCacheKey);
        if ($coolDownItemCache->isHit()) {
            $remainingCoolDownTime = (int)($coolDownItemCache->get() - microtime(true));

            return $this->translator->trans(
                'commands.triggered_cooldown',
                ['%remainingCoolDownTime%' => $remainingCoolDownTime],
                'commands');
        }

        $coolDownItemCache->expiresAfter(30);
        $coolDownItemCache->set(30 + microtime(true));
        $this->cache->save($coolDownItemCache);

        return null;
    }
}
