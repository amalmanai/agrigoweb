<?php

namespace App\Repository;

use App\Entity\Vente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vente>
 */
class VenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }
    public function getTotalRevenue(): float
    {
        // Since price column is removed, return 0 or implement differently
        return 0.0;
    }

    /**
     * Ventes front : liées à une récolte appartenant à l'utilisateur.
     *
     * @return Vente[]
     */
    public function findForUser(int $userId): array
    {
        return $this->createQueryBuilder('v')
            ->innerJoin('v.recolte', 'r')
            ->andWhere('r.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('v.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenueForUser(int $userId): float
    {
        // Since price column is removed, return 0
        return 0.0;
    }

    public function adminSearch(?string $search, string $sort = 'id', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('v');

        if ($search) {
            $qb->andWhere('v.description LIKE :search OR v.buyerName LIKE :search OR v.status LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSorts = ['id', 'description', 'price', 'saleDate', 'buyerName', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('v.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
