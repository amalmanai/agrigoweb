<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlerteRisque;
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
    public function findFiltered(?string $search = null, string $sortField = 'dateAlerte', string $sortDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.culture', 'c')
            ->addSelect('c');

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
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'a.dateAlerte';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
            static fn (array $row): array => [
                'type' => (string) $row['type'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}
