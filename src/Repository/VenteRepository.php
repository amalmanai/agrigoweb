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
}
