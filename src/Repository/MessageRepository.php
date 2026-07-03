<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Tous les messages d'une conversation, du plus ancien au plus récent
     * (ordre d'affichage du fil).
     *
     * @return Message[]
     */
    public function findThread(Conversation $conversation): array
    {
        return $this->findBy(['conversation' => $conversation], ['createdAt' => 'ASC']);
    }

    /**
     * Dernier message d'une conversation (aperçu dans la liste admin).
     */
    public function findLastForConversation(Conversation $conversation): ?Message
    {
        return $this->findOneBy(['conversation' => $conversation], ['createdAt' => 'DESC']);
    }

    /**
     * Nombre de messages non lus par le client : messages envoyés par
     * l'admin et pas encore marqués lus. Sert au compteur côté descendant.
     */
    public function findUnreadCountForClient(User $client): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.conversation', 'c')
            ->where('c.client = :client')
            ->andWhere('m.isFromAdmin = :fromAdmin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('client', $client)
            ->setParameter('fromAdmin', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre total de messages clients non lus par l'admin, toutes
     * conversations confondues (l'admin est unique dans l'app).
     */
    public function findUnreadCountForAdmin(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.isFromAdmin = :fromAdmin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('fromAdmin', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Identifiants des conversations comportant des messages client non lus
     * par l'admin (une seule requête groupée). Sert à afficher un badge par
     * ligne dans la liste admin sans faire de N+1.
     *
     * @return int[]
     */
    public function findUnreadConversationIdsForAdmin(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.conversation) AS cid')
            ->where('m.isFromAdmin = :fromAdmin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('fromAdmin', false)
            ->groupBy('m.conversation')
            ->getQuery()
            ->getArrayResult();

        return array_map('intval', array_column($rows, 'cid'));
    }

    /**
     * Marque comme lus tous les messages envoyés par l'admin dans une
     * conversation (appelé quand le client ouvre le fil).
     */
    public function markAdminToClientRead(Conversation $conversation): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.conversation = :conversation')
            ->andWhere('m.isFromAdmin = :fromAdmin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('conversation', $conversation)
            ->setParameter('fromAdmin', true)
            ->getQuery()
            ->execute();
    }

    /**
     * Marque comme lus tous les messages envoyés par le client dans une
     * conversation (appelé quand l'admin ouvre le fil ou répond).
     */
    public function markClientToAdminRead(Conversation $conversation): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.conversation = :conversation')
            ->andWhere('m.isFromAdmin = :fromAdmin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('conversation', $conversation)
            ->setParameter('fromAdmin', false)
            ->getQuery()
            ->execute();
    }
}