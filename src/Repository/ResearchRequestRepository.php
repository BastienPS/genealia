<?php

namespace App\Repository;

use App\Entity\ResearchRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResearchRequest>
 */
class ResearchRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResearchRequest::class);
    }

    /**
     * Demandes actives (non archivées) d'un client donné, triées de la plus
     * récente à la plus ancienne. Sert de base à l'espace client — ne renvoie
     * jamais les demandes d'autrui ni les demandes archivées (du point de vue
     * du client, une demande archivée est supprimée).
     *
     * @return ResearchRequest[]
     */
    public function findByClient(User $client): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.client = :client')
            ->andWhere('r.status != :archived')
            ->setParameter('client', $client)
            ->setParameter('archived', 'archived')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une demande active (non archivée) appartenant au client, ou
     * null si elle n'existe pas, appartient à un autre compte, ou est archivée.
     * C'est la protection IDOR des routes côté client (détail, confirmer/refuser
     * la suppression) : une demande archivée renvoie null → 404.
     */
    public function findOneByClientAndId(User $client, int $id): ?ResearchRequest
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.id = :id')
            ->andWhere('r.client = :client')
            ->andWhere('r.status != :archived')
            ->setParameter('id', $id)
            ->setParameter('client', $client)
            ->setParameter('archived', 'archived')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Demandes archivées d'un client (supprimées de son point de vue mais
     * conservées en base), affichées dans une section à part sur le tableau
     * de bord. Triées de la plus récente à la plus ancienne.
     *
     * @return ResearchRequest[]
     */
    public function findArchivedByClient(User $client): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.client = :client')
            ->andWhere('r.status = :archived')
            ->setParameter('client', $client)
            ->setParameter('archived', 'archived')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Listage côté admin : demandes actives par défaut, ou uniquement les
     * archivées si $archived = true.
     *
     * @return ResearchRequest[]
     */
    public function findAllForAdmin(bool $archived = false): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        if ($archived) {
            $qb->andWhere('r.status = :archived')
                ->setParameter('archived', 'archived');
        } else {
            $qb->andWhere('r.status != :archived')
                ->setParameter('archived', 'archived');
        }

        return $qb->getQuery()
            ->getResult();
    }
}