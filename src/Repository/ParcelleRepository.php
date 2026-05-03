<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\ParcelleSummaryDto;
use App\Entity\Parcelle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findFiltered(?string $search = null, string $sortField = 'nomParcelle', string $sortDirection = 'ASC', ?User $owner = null): array
    {
        return $this->findFilteredQueryBuilder($search, $sortField, $sortDirection, $owner)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Parcelle[]
     */
    public function findFilteredByOwner(User $owner, ?string $search = null, string $sortField = 'nomParcelle', string $sortDirection = 'ASC'): array
    {
        return $this->findFiltered($search, $sortField, $sortDirection, $owner);
    }

    public function findFilteredQueryBuilder(?string $search = null, string $sortField = 'nomParcelle', string $sortDirection = 'ASC', ?User $owner = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if ($owner !== null) {
            $qb->andWhere('p.owner = :owner')
                ->setParameter('owner', $owner);
        }

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
            ->addOrderBy('p.id', 'ASC');
    }

    public function countAll(?User $owner = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if ($owner !== null) {
            $qb->andWhere('p.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalSurface(?User $owner = null): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.surface)');

        if ($owner !== null) {
            $qb->andWhere('p.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * @return array<int, array{id:int, nomParcelle:string, surface:float, coordonneesGps:?string, typeSol:?string, cultureCount:int}>
     */
    public function findParcelleSummariesWithCultureCount(?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('NEW App\Dto\ParcelleSummaryDto(p.id, p.nomParcelle, p.surface, p.coordonneesGps, p.typeSol, COUNT(c.id))');

        if ($owner !== null) {
            $qb->andWhere('p.owner = :owner')
                ->setParameter('owner', $owner);

            $qb->leftJoin('p.cultures', 'c', 'WITH', 'c.owner = :owner');
        } else {
            $qb->leftJoin('p.cultures', 'c');
        }

        $cacheKey = 'parcelle_summaries_with_culture_count_' . ($owner?->getIdUser() ?? 'all');

        $rows = $qb
            ->groupBy('p.id, p.nomParcelle, p.surface, p.coordonneesGps, p.typeSol')
            ->orderBy('p.nomParcelle', 'ASC')
            ->getQuery()
            ->enableResultCache(120, $cacheKey)
            ->getResult();

        return array_map(
            static fn(ParcelleSummaryDto $row): array => [
                'id' => $row->id,
                'nomParcelle' => $row->nomParcelle,
                'surface' => $row->surface,
                'coordonneesGps' => $row->coordonneesGps,
                'typeSol' => $row->typeSol,
                'cultureCount' => $row->cultureCount,
            ],
            $rows
        );
    }
}
