<?php

namespace App\Repository;

use App\Entity\Tache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tache>
 */
class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }

    /**
     * @return Tache[]
     */
    public function findByOwnerId(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id_user = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
