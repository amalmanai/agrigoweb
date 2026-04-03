<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HistoriqueCulture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueCulture>
 */
class HistoriqueCultureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueCulture::class);
    }

    /**
     * @return HistoriqueCulture[]
     */
    public function findAllLatestFirst(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.dateRecolteEffective', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return HistoriqueCulture[]
     */
    public function findFiltered(?string $search = null, string $sortField = 'dateRecolteEffective', string $sortDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.parcelle', 'p')
            ->addSelect('p');

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(COALESCE(h.ancienneCulture, :emptyCulture)) LIKE :search OR LOWER(COALESCE(p.nomParcelle, :emptyParcel)) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%')
                ->setParameter('emptyCulture', '')
                ->setParameter('emptyParcel', '');
        }

        $allowedSortFields = [
            'id' => 'h.id',
            'ancienneCulture' => 'h.ancienneCulture',
            'dateRecolteEffective' => 'h.dateRecolteEffective',
            'rendementFinal' => 'h.rendementFinal',
            'parcelle' => 'p.nomParcelle',
        ];

        $sortExpression = $allowedSortFields[$sortField] ?? 'h.dateRecolteEffective';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortExpression, $sortDirection)
            ->addOrderBy('h.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageRendementFinal(): float
    {
        $result = $this->createQueryBuilder('h')
            ->select('AVG(h.rendementFinal)')
            ->andWhere('h.rendementFinal IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    public function countRecoltesByYear(int $year): int
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $end = $start->modify('+1 year');

        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.dateRecolteEffective >= :start')
            ->andWhere('h.dateRecolteEffective < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
