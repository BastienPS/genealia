<?php

namespace App\Repository;

use App\Entity\Ancestor;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ancestor>
 */
class AncestorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ancestor::class);
    }

    /**
     * Ancêtres d'un client donné, triés par nom de famille puis prénom.
     * Base de la liste « Mes ancêtres » — ne renvoie jamais ceux d'autrui.
     *
     * @return Ancestor[]
     */
    public function findByClient(User $client): array
    {
        return $this->findBy(['client' => $client], ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    /**
     * Récupère un ancêtre précis appartenant au client, ou null s'il
     * n'existe pas ou appartient à un autre compte. Protection IDOR des
     * routes de modification / suppression côté client.
     */
    public function findOneByClientAndId(User $client, int $id): ?Ancestor
    {
        return $this->findOneBy(['id' => $id, 'client' => $client]);
    }

    /**
     * Tous les ancêtres de tous les clients, triés par client puis par nom.
     * Le client est hydraté en jointure (addSelect) pour éviter une requête
     * N+1 lors du rendu de la liste d'administration. Base de la vue admin.
     *
     * @return Ancestor[]
     */
    public function findAllWithClientOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')->addSelect('c')
            ->orderBy('c.email', 'ASC')
            ->addOrderBy('a.lastName', 'ASC')
            ->addOrderBy('a.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}