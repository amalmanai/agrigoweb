<?php

namespace App\Repository;

use App\Entity\Recolte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recolte>
 */
class RecolteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recolte::class);
    }
    public function getTotalProductionCost(): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.productionCost) as total');

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * @return Recolte[] Returns an array of Recolte objects
     */
    public function searchAndSort(?string $query, string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($query) {
            $qb->andWhere('r.name LIKE :val OR r.id = :id_val')
               ->setParameter('val', '%' . $query . '%')
               ->setParameter('id_val', is_numeric($query) ? $query : null);
        }

        $qb->orderBy('r.name', $sortOrder === 'DESC' ? 'DESC' : 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Liste front : uniquement les récoltes de l'utilisateur connecté.
     *
     * @return Recolte[]
     */
    public function searchAndSortForUser(int $userId, ?string $query, string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.userId = :uid')
            ->setParameter('uid', $userId);

        if ($query) {
            $qb->andWhere('r.name LIKE :val OR r.id = :id_val')
               ->setParameter('val', '%' . $query . '%')
               ->setParameter('id_val', is_numeric($query) ? $query : null);
        }

        $qb->orderBy('r.name', $sortOrder === 'DESC' ? 'DESC' : 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getTotalProductionCostForUser(int $userId): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.productionCost) as total')
            ->andWhere('r.userId = :uid')
            ->setParameter('uid', $userId);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function adminSearch(?string $search, string $sort = 'id', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($search) {
            $qb->andWhere('r.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSorts = ['id', 'name', 'quantity', 'productionCost', 'harvestDate'];
        if (in_array($sort, $allowedSorts)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('r.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
