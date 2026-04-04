<?php

namespace App\Repository;

use App\Entity\HistoriqueIrrigation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueIrrigation>
 */
class HistoriqueIrrigationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueIrrigation::class);
    }
}
