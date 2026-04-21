<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlerteRisque;
use App\Entity\Culture;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlerteRisque>
 */
class AlerteRisqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlerteRisque::class);
    }

    /**
     * @return AlerteRisque[]
     */
    public function findAllLatestFirst(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateAlerte', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AlerteRisque[]
     */
    public function findFiltered(?string $search = null, string $sortField = 'dateAlerte', string $sortDirection = 'DESC', ?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.culture', 'c')
            ->addSelect('c');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(COALESCE(a.typeAlerte, :emptyType)) LIKE :search OR LOWER(COALESCE(a.description, :emptyDescription)) LIKE :search OR LOWER(COALESCE(c.nomCulture, :emptyCulture)) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%')
                ->setParameter('emptyType', '')
                ->setParameter('emptyDescription', '')
                ->setParameter('emptyCulture', '');
        }

        $allowedSortFields = [
            'id' => 'a.id',
            'typeAlerte' => 'a.typeAlerte',
            'dateAlerte' => 'a.dateAlerte',
            'culture' => 'c.nomCulture',
            'severity' => 'a.severity',
            'isResolved' => 'a.isResolved',
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'a.dateAlerte';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(?User $owner = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->leftJoin('a.culture', 'c');

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array{labels:string[], totals:int[], red:int[], yellow:int[]}
     */
    public function getTrendLastDays(int $days = 7, ?User $owner = null): array
    {
        $days = max(1, $days);
        $start = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $days - 1));

        $ownerClause = '';
        $parameters = ['start' => $start->format('Y-m-d 00:00:00')];
        if ($owner !== null) {
            $ownerClause = ' AND c.owner_id = :ownerId';
            $parameters['ownerId'] = $owner->getIdUser();
        }

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(a.date_alerte) AS day, a.severity, COUNT(*) AS total
             FROM alertes_risques a
             LEFT JOIN cultures c ON c.id_culture = a.id_culture
             WHERE a.date_alerte >= :start' . $ownerClause . '
             GROUP BY DATE(date_alerte), severity
             ORDER BY day ASC',
            $parameters
        )->fetchAllAssociative();

        $index = [];
        for ($i = 0; $i < $days; ++$i) {
            $day = $start->modify(sprintf('+%d days', $i))->format('Y-m-d');
            $index[$day] = [
                'total' => 0,
                'red' => 0,
                'yellow' => 0,
            ];
        }

        foreach ($rows as $row) {
            $day = (string) ($row['day'] ?? '');
            if (!isset($index[$day])) {
                continue;
            }

            $severity = (string) ($row['severity'] ?? '');
            $total = (int) ($row['total'] ?? 0);

            $index[$day]['total'] += $total;
            if ($severity === AlerteRisque::SEVERITY_RED) {
                $index[$day]['red'] += $total;
            }
            if ($severity === AlerteRisque::SEVERITY_YELLOW) {
                $index[$day]['yellow'] += $total;
            }
        }

        return [
            'labels' => array_keys($index),
            'totals' => array_values(array_map(static fn(array $item): int => (int) $item['total'], $index)),
            'red' => array_values(array_map(static fn(array $item): int => (int) $item['red'], $index)),
            'yellow' => array_values(array_map(static fn(array $item): int => (int) $item['yellow'], $index)),
        ];
    }

    public function countUnresolved(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.isResolved = :resolved')
            ->setParameter('resolved', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnresolvedBySeverity(string $severity, ?User $owner = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.culture', 'c')
            ->select('COUNT(a.id)')
            ->andWhere('a.isResolved = :resolved')
            ->andWhere('a.severity = :severity')
            ->setParameter('resolved', false)
            ->setParameter('severity', $severity);

        if ($owner !== null) {
            $qb->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return AlerteRisque[]
     */
    public function findUnresolvedByCulture(Culture $culture): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.culture = :culture')
            ->andWhere('a.isResolved = :resolved')
            ->setParameter('culture', $culture)
            ->setParameter('resolved', false)
            ->orderBy('a.dateAlerte', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOpenByCultureAndType(Culture $culture, string $typeAlerte): ?AlerteRisque
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.culture = :culture')
            ->andWhere('a.typeAlerte = :typeAlerte')
            ->andWhere('a.isResolved = :resolved')
            ->setParameter('culture', $culture)
            ->setParameter('typeAlerte', $typeAlerte)
            ->setParameter('resolved', false)
            ->orderBy('a.dateAlerte', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countSince(
        \DateTimeImmutable $date
    ): int {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.dateAlerte >= :start')
            ->setParameter('start', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{type:string, total:int}>
     */
    public function countByType(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('COALESCE(a.typeAlerte, :inconnu) AS type, COUNT(a.id) AS total')
            ->setParameter('inconnu', 'Non defini')
            ->groupBy('a.typeAlerte')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn(array $row): array => [
                'type' => (string) $row['type'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}
