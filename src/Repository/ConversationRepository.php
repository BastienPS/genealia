<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Renvoie la conversation unique du client, ou en crée une nouvelle
     * (persistée + flushée) si elle n'existe pas encore. Utilisé côté client
     * (résolu depuis l'utilisateur connecté) et côté admin (depuis un
     * identifiant de client vérifié).
     *
     * L'unicité (OneToOne unique sur client) garantit qu'il ne peut y avoir
     * qu'une seule conversation par descendant.
     */
    public function findOrCreateForClient(User $client): Conversation
    {
        $conversation = $this->findOneBy(['client' => $client]);

        if ($conversation !== null) {
            return $conversation;
        }

        $conversation = new Conversation();
        $conversation->setClient($client);

        $this->getEntityManager()->persist($conversation);
        $this->getEntityManager()->flush();

        return $conversation;
    }

    /**
     * Toutes les conversations, triées par dernière activité (la plus récente
     * d'abord). NULLS LAST gère les conversations sans message — SQLite natif.
     *
     * @return Conversation[]
     */
    public function findAllOrderByLastActivity(): array
    {
        // Sous SQLite, un tri DESC place les NULL en dernier par défaut :
        // les conversations sans message (lastActivityAt NULL) remontent donc
        // en bas, ce qui correspond à un tri « NULLS LAST ». On évite ici la
        // syntaxe NULLS LAST qui n'est pas supportée par le parseur DQL.
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'u')
            ->addSelect('u')
            ->orderBy('c.lastActivityAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}