<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\ProduitComment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProduitComment>
 */
class ProduitCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitComment::class);
    }

    /**
     * @return ProduitComment[]
     */
    public function findByProduitNewestFirst(Produit $produit): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.produit = :produit')
            ->setParameter('produit', $produit)
            ->orderBy('c.date_commentaire', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id_commentaire)')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
