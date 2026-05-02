<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function adminSearch(?string $search, string $sort = 'id_produit', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.nom_produit LIKE :search OR p.categorie LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSorts = ['id_produit', 'nom_produit', 'categorie', 'quantite_disponible', 'prix_unitaire', 'seuil_alerte'];
        if (in_array($sort, $allowedSorts)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('p.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select(
            'COUNT(p.id_produit) AS totalProducts',
            'COALESCE(SUM(p.quantite_disponible), 0) AS totalQuantity',
            'COALESCE(AVG(p.prix_unitaire), 0) AS averagePrice',
            'SUM(CASE WHEN p.quantite_disponible <= p.seuil_alerte THEN 1 ELSE 0 END) AS lowStockCount'
        );

        $stats = $qb->getQuery()->getSingleResult();

        return [
            'totalProducts' => (int) $stats['totalProducts'],
            'totalQuantity' => (int) $stats['totalQuantity'],
            'averagePrice' => (float) $stats['averagePrice'],
            'lowStockCount' => (int) $stats['lowStockCount'],
        ];
    }

    public function getCategoryCounts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.categorie AS categorie', 'COUNT(p.id_produit) AS productCount')
            ->groupBy('p.categorie')
            ->orderBy('productCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
