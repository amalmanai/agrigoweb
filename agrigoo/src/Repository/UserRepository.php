<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findByAdvancedFilters(?string $query, ?string $role, ?string $status, ?string $sortBy = 'idUser', ?string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.nomUser', ':query'),
                    $qb->expr()->like('u.prenomUser', ':query'),
                    $qb->expr()->like('u.emailUser', ':query'),
                    $qb->expr()->like('u.numUser', ':query')
                )
            )->setParameter('query', '%' . $query . '%');
        }

        if ($role) {
            $qb->andWhere('u.roleUser = :role')
               ->setParameter('role', $role);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('u.isActive = :status')
               ->setParameter('status', $status === '1');
        }

        $validSortFields = ['idUser', 'nomUser', 'prenomUser', 'emailUser', 'roleUser', 'isActive'];
        if (in_array($sortBy, $validSortFields)) {
            $qb->orderBy('u.' . $sortBy, strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('u.idUser', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
