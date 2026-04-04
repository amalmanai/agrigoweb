<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Culture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Culture>
 */
class CultureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Culture::class);
    }

    /**
     * @return Culture[]
     */
    public function findByParcelle(int $parcelleId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parcelle = :parcelleId')
            ->setParameter('parcelleId', $parcelleId)
            ->orderBy('c.dateSemis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Culture[]
     */
    public function findByEtatCroissance(string $etat): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.etatCroissance = :etat')
            ->setParameter('etat', $etat)
            ->orderBy('c.dateSemis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Culture[]
     */
    public function findFiltered(?string $search = null, string $sortField = 'dateSemis', string $sortDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.parcelle', 'p')
            ->addSelect('p');

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(c.nomCulture) LIKE :search OR LOWER(COALESCE(c.etatCroissance, :emptyState)) LIKE :search OR LOWER(COALESCE(p.nomParcelle, :emptyParcel)) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%')
                ->setParameter('emptyState', '')
                ->setParameter('emptyParcel', '');
        }

        $allowedSortFields = [
            'id' => 'c.id',
            'nomCulture' => 'c.nomCulture',
            'dateSemis' => 'c.dateSemis',
            'etatCroissance' => 'c.etatCroissance',
            'rendementPrevu' => 'c.rendementPrevu',
            'parcelle' => 'p.nomParcelle',
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'c.dateSemis';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageRendementPrevu(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('AVG(c.rendementPrevu)')
            ->andWhere('c.rendementPrevu IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * @return array<int, array{etat:string, total:int}>
     */
    public function countByEtatCroissance(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('COALESCE(c.etatCroissance, :inconnu) AS etat, COUNT(c.id) AS total')
            ->setParameter('inconnu', 'Non defini')
            ->groupBy('c.etatCroissance')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'etat' => (string) $row['etat'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}
