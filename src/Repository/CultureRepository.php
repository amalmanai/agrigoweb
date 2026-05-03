<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Culture;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findByParcelle(int $parcelleId, ?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.parcelle = :parcelleId')
            ->setParameter('parcelleId', $parcelleId);

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb
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
    public function findFiltered(?string $search = null, string $sortField = 'dateSemis', string $sortDirection = 'DESC', ?User $owner = null): array
    {
        return $this->findFilteredQueryBuilder($search, $sortField, $sortDirection, $owner)
            ->getQuery()
            ->getResult();
    }

    public function findFilteredQueryBuilder(?string $search = null, string $sortField = 'dateSemis', string $sortDirection = 'DESC', ?User $owner = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.parcelle', 'p')
            ->addSelect('p');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

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
            'dateRecolteEstimee' => 'c.dateRecolteEstimee',
            'etatCroissance' => 'c.etatCroissance',
            'rendementPrevu' => 'c.rendementPrevu',
            'rendementEstime' => 'c.rendementEstime',
            'parcelle' => 'p.nomParcelle',
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'c.dateSemis';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('c.id', 'DESC');
    }

    public function countAll(?User $owner = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Culture[]
     */
    public function findActiveCultures(?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.etatCroissance IS NULL OR c.etatCroissance != :harvestDone')
            ->setParameter('harvestDone', 'Recolte termine');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb
            ->orderBy('c.dateSemis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageRendementPrevu(?User $owner = null): float
    {
        $qb = $this->createQueryBuilder('c')
            ->select('AVG(c.rendementPrevu)')
            ->andWhere('c.rendementPrevu IS NOT NULL');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * @return array<int, array{etat:string, total:int}>
     */
    public function countByEtatCroissance(?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COALESCE(c.etatCroissance, :inconnu) AS etat, COUNT(c.id) AS total')
            ->setParameter('inconnu', 'Non defini')
            ->groupBy('c.etatCroissance');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $rows = $qb
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn(array $row): array => [
                'etat' => (string) $row['etat'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}
