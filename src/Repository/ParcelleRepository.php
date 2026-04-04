<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Parcelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parcelle>
 */
class ParcelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcelle::class);
    }

    /**
     * @return Parcelle[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nomParcelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Parcelle[]
     */
    public function findFiltered(?string $search = null, string $sortField = 'nomParcelle', string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(p.nomParcelle) LIKE :search OR LOWER(COALESCE(p.typeSol, :emptyType)) LIKE :search OR LOWER(COALESCE(p.coordonneesGps, :emptyCoords)) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%')
                ->setParameter('emptyType', '')
                ->setParameter('emptyCoords', '');
        }

        $allowedSortFields = [
            'id' => 'p.id',
            'nomParcelle' => 'p.nomParcelle',
            'surface' => 'p.surface',
            'typeSol' => 'p.typeSol',
            'coordonneesGps' => 'p.coordonneesGps',
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'p.nomParcelle';
        $sortDirection = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalSurface(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.surface)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * @return array<int, array{id:int, nomParcelle:string, surface:float, coordonneesGps:?string, typeSol:?string, cultureCount:int}>
     */
    public function findParcelleSummariesWithCultureCount(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.id AS id, p.nomParcelle AS nomParcelle, p.surface AS surface, p.coordonneesGps AS coordonneesGps, p.typeSol AS typeSol, COUNT(c.id) AS cultureCount')
            ->leftJoin('p.cultures', 'c')
            ->groupBy('p.id, p.nomParcelle, p.surface, p.coordonneesGps, p.typeSol')
            ->orderBy('p.nomParcelle', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'nomParcelle' => (string) $row['nomParcelle'],
                'surface' => (float) $row['surface'],
                'coordonneesGps' => isset($row['coordonneesGps']) ? (string) $row['coordonneesGps'] : null,
                'typeSol' => isset($row['typeSol']) ? (string) $row['typeSol'] : null,
                'cultureCount' => (int) $row['cultureCount'],
            ],
            $rows
        );
    }
}
