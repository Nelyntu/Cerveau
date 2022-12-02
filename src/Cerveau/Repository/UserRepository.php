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
        // find locally by name
        $user = $this->getLocallyByLogin($username);

        if ($user !== null) {
            return $user;
        }

        // find by api
        $apiUser = $this->userApi->getUserByName($username);

        // is ID known ?
        $user = $this->getById($apiUser->id);
        if(!$user instanceof \Cerveau\Entity\User) {
            // new user
            $user = new User($apiUser->id, $apiUser->login, $apiUser->name);
            $this->entityManager->persist($user);
        } else {
            // user changed his name
            $user->update($apiUser->login, $apiUser->name);
        }

        $this->entityManager->flush();

        return $user;
    }

    private function getById(int $id): ?User
    {
        /** @var ?User $user */
        $user = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
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
