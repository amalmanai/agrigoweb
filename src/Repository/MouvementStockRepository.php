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

    /**
     * Retourne la série mensuelle des sorties (consommation) par produit.
     *
     * Format:
     *  [
     *    12 => ['2026-01' => 10, '2026-02' => 3, ...],
     *    13 => [...],
     *  ]
     */
    public function monthlyOutboundByProduct(int $windowMonths = 12): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Filtre sur les derniers mois pour limiter le volume.
        // On inclut le mois courant + (windowMonths-1) mois avant.
        $sql = <<<'SQL'
SELECT
  ms.id_produit AS produit_id,
  DATE_FORMAT(ms.date_mouvement, '%Y-%m') AS month_key,
  SUM(ms.quantite) AS total_qty
FROM mouvement_stock ms
WHERE LOWER(TRIM(ms.type_mouvement)) = 'sortie'
  AND ms.date_mouvement >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
GROUP BY ms.id_produit, month_key
ORDER BY ms.id_produit ASC, month_key ASC
SQL;

        $months = max(1, $windowMonths);
        $rows = $conn->executeQuery($sql, ['months' => $months])->fetchAllAssociative();

        $out = [];
        foreach ($rows as $row) {
            $pid = (int) $row['produit_id'];
            $month = (string) $row['month_key'];
            $qty = (int) $row['total_qty'];
            $out[$pid][$month] = $qty;
        }

        return $out;
    }
}
