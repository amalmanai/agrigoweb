<?php

namespace App\Repository;

use App\Entity\MouvementStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MouvementStock>
 */
class MouvementStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementStock::class);
    }

    public function adminSearch(?string $search, string $sort = 'id_mouvement', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($search) {
            $qb->andWhere('m.type_mouvement LIKE :search OR m.motif LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSorts = ['id_mouvement', 'type_mouvement', 'date_mouvement', 'quantite', 'motif', 'id_produit'];
        if (in_array($sort, $allowedSorts)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            if ($sort === 'id_produit') {
                $qb->leftJoin('m.produit', 'p')
                   ->orderBy('p.id_produit', $direction);
            } else {
                $qb->orderBy('m.' . $sort, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
