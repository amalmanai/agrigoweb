<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MarketplaceOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketplaceOrder>
 */
class MarketplaceOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketplaceOrder::class);
    }

    /**
     * @return MarketplaceOrder[]
     */
    public function findPurchasesForBuyer(int $buyerId): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.vente', 'v')->addSelect('v')
            ->leftJoin('v.recolte', 'r')->addSelect('r')
            ->andWhere('IDENTITY(o.buyer) = :buyerId')
            ->setParameter('buyerId', $buyerId)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MarketplaceOrder[]
     */
    public function findSalesForSeller(int $sellerId): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.vente', 'v')->addSelect('v')
            ->leftJoin('v.recolte', 'r')->addSelect('r')
            ->leftJoin('o.buyer', 'b')->addSelect('b')
            ->andWhere('o.sellerId = :sellerId')
            ->setParameter('sellerId', $sellerId)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    public function findOrderedVenteIdsForBuyer(int $buyerId): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.vente) AS venteId')
            ->andWhere('IDENTITY(o.buyer) = :buyerId')
            ->setParameter('buyerId', $buyerId)
            ->groupBy('o.vente')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['venteId'], $rows);
    }

    /**
     * @return string[]
     */
    public function findPurchasedProductNames(int $buyerId): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('r.name AS productName')
            ->innerJoin('o.vente', 'v')
            ->innerJoin('v.recolte', 'r')
            ->andWhere('IDENTITY(o.buyer) = :buyerId')
            ->setParameter('buyerId', $buyerId)
            ->andWhere('r.name IS NOT NULL')
            ->groupBy('r.name')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): string => trim((string) $row['productName']), $rows)));
    }
}
