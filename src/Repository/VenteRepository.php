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
        $qb = $this->createQueryBuilder('v')
            ->select('SUM(v.price) as total');

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
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
        $qb = $this->createQueryBuilder('v')
            ->select('SUM(v.price) as total')
            ->innerJoin('v.recolte', 'r')
            ->andWhere('r.userId = :uid')
            ->setParameter('uid', $userId);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
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

    /**
     * @return Vente[]
     */
    public function findMarketplaceListingsForBuyer(int $buyerId, ?string $search = null, int $limit = 24): array
    {
        $qb = $this->createQueryBuilder('v')
            ->innerJoin('v.recolte', 'r')
            ->addSelect('r')
            ->andWhere('v.isMarketplaceListing = :listing')
            ->andWhere('(v.availableQuantity > 0 OR v.availableQuantity IS NULL)')
            ->andWhere('r.userId <> :buyerId')
            ->setParameter('listing', true)
            ->setParameter('buyerId', $buyerId)
            ->orderBy('v.saleDate', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults($limit);

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('LOWER(v.description) LIKE :search OR LOWER(r.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $keywords
     * @param int[] $excludeVenteIds
     *
     * @return Vente[]
     */
    public function findRecommendedForBuyer(int $buyerId, array $keywords, array $excludeVenteIds = [], int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('v')
            ->innerJoin('v.recolte', 'r')
            ->addSelect('r')
            ->andWhere('v.isMarketplaceListing = :listing')
            ->andWhere('(v.availableQuantity > 0 OR v.availableQuantity IS NULL)')
            ->andWhere('r.userId <> :buyerId')
            ->setParameter('listing', true)
            ->setParameter('buyerId', $buyerId)
            ->orderBy('v.rating', 'DESC')
            ->addOrderBy('v.saleDate', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults($limit);

        if ($excludeVenteIds !== []) {
            $qb->andWhere($qb->expr()->notIn('v.id', ':excludeIds'))
                ->setParameter('excludeIds', $excludeVenteIds);
        }

        $keywords = array_values(array_filter(array_map(static fn (string $k): string => trim($k), $keywords)));
        if ($keywords !== []) {
            $orX = $qb->expr()->orX();
            foreach (array_slice($keywords, 0, 5) as $index => $keyword) {
                $paramName = 'kw' . $index;
                $orX->add('LOWER(r.name) LIKE :' . $paramName);
                $qb->setParameter($paramName, '%' . mb_strtolower($keyword) . '%');
            }

            if ($orX->count() > 0) {
                $qb->andWhere($orX);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
