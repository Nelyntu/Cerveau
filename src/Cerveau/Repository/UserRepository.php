<?php

namespace Cerveau\Repository;

use Cerveau\Entity\User;
use Cerveau\Twitch\Follower;
use Cerveau\Twitch\UserApi;
use Cerveau\Twitch\UserNotFound;
use Doctrine\ORM\EntityManagerInterface;

class UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserApi $userApi,
    )
    {
    }

    /**
     * @throws UserNotFound
     */
    public function getOrCreateByUsername(string $username): User
    {
        $user = $this->getLocallyByLogin($username);

        if ($user !== null) {
            return $user;
        }

        $apiUser = $this->userApi->getUserByName($username);
        // TODO : don't create if id already known (case : user rename)
        $user = new User($apiUser->id, $apiUser->login, $apiUser->name);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getLocallyByLogin(string $login): ?User
    {
        /** @var ?User $user */
        $user = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.login = :username')
            ->setParameter('username', $login)
            ->getQuery()
            ->getOneOrNullResult();
        return $user;
    }

    public function refreshFrom(Follower $follower): void
    {
        $user = $this->getLocallyByLogin($follower->login);

        if ($user instanceof User) {
            $user->update($follower->login, $follower->name);
        } else {
            $user = new User($follower->id, $follower->login, $follower->name);
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }
}
