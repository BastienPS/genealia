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
     * Demandes d'un client donné, triées de la plus récente à la plus ancienne.
     * Sert de base à l'espace client — ne renvoie jamais les demandes d'autrui.
     *
     * @return ResearchRequest[]
     */
    public function findByClient(User $client): array
    {
        return $this->findBy(['client' => $client], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère une demande précise appartenant au client, ou null si elle
     * n'existe pas ou appartient à un autre compte. C'est la protection IDOR
     * de la route de détail côté client.
     */
    public function findOneByClientAndId(User $client, int $id): ?ResearchRequest
    {
        return $this->findOneBy(['id' => $id, 'client' => $client]);
    }
}
