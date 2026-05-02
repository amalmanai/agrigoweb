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

    public function findMarketplaceListingsForBuyer(int $buyerId, ?string $search = '', int $limit = 30): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.recolte', 'r')
            ->andWhere('r.userId IS NULL OR r.userId != :buyerId')
            ->setParameter('buyerId', $buyerId)
            ->andWhere('v.status != :status')
            ->setParameter('status', 'Completed')
            ->setMaxResults($limit);

        if ($search) {
            $qb->andWhere('v.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findRecommendedForBuyer(int $buyerId, array $keywords, array $excludeVenteIds, int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.recolte', 'r')
            ->andWhere('r.userId IS NULL OR r.userId != :buyerId')
            ->setParameter('buyerId', $buyerId)
            ->andWhere('v.status != :status')
            ->setParameter('status', 'Completed')
            ->setMaxResults($limit);

        if (!empty($excludeVenteIds)) {
            $qb->andWhere('v.id NOT IN (:excludeIds)')
                ->setParameter('excludeIds', $excludeVenteIds);
        }

        if (!empty($keywords)) {
            $orX = $qb->expr()->orX();
            foreach ($keywords as $i => $keyword) {
                $orX->add($qb->expr()->like('v.description', ':kw' . $i));
                $orX->add($qb->expr()->like('r.name', ':kw' . $i));
                $qb->setParameter('kw' . $i, '%' . $keyword . '%');
            }
            $qb->andWhere($orX);
        }

        return $qb->getQuery()->getResult();
    }
}
