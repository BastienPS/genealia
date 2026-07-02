<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Resolve a local User for an OAuth login, merging accounts by email.
     *
     * If a User with the provider's email already exists (e.g. previously
     * logged in via the other provider), it is reused and the matching
     * provider ID is attached. Otherwise a fresh ROLE_USER account is created.
     */
    public function findOrCreateFromOAuth(string $email, string $displayName, string $provider, string $providerId): User
    {
        $user = $this->findOneByEmail($email);

        if ($user === null) {
            $user = (new User())
                ->setEmail($email)
                ->setDisplayName($displayName)
                ->setRoles(['ROLE_USER']);
        }

        if ($provider === 'google' && $user->getGoogleId() === null) {
            $user->setGoogleId($providerId);
        }
        if ($provider === 'facebook' && $user->getFacebookId() === null) {
            $user->setFacebookId($providerId);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}