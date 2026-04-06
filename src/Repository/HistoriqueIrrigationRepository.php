<?php

namespace App\Repository;

use App\Entity\HistoriqueIrrigation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueIrrigation>
 */
class HistoriqueIrrigationRepository extends ServiceEntityRepository
{
    /** @var list<string> */
    private const TRIS_VALIDES = [
        'date_desc', 'date_asc',
        'nom_asc', 'nom_desc',
        'duree_asc', 'duree_desc',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueIrrigation::class);
    }

    /**
     * @return list<HistoriqueIrrigation>
     */
    public function findAllFiltered(?string $rechercheNomSysteme = null, string $tri = 'date_desc'): array
    {
        if (!\in_array($tri, self::TRIS_VALIDES, true)) {
            $tri = 'date_desc';
        }

        $qb = $this->createQueryBuilder('h')
            ->innerJoin('h.systemeIrrigation', 'sys')->addSelect('sys');

        if (null !== $rechercheNomSysteme && '' !== trim($rechercheNomSysteme)) {
            $term = '%'.\mb_strtolower(trim($rechercheNomSysteme)).'%';
            $qb->andWhere('LOWER(sys.nom_systeme) LIKE :rq')
                ->setParameter('rq', $term);
        }

        match ($tri) {
            'date_asc' => $qb->orderBy('h.date_irrigation', 'ASC'),
            'nom_asc' => $qb->orderBy('sys.nom_systeme', 'ASC'),
            'nom_desc' => $qb->orderBy('sys.nom_systeme', 'DESC'),
            'duree_asc' => $qb->orderBy('h.duree_minutes', 'ASC'),
            'duree_desc' => $qb->orderBy('h.duree_minutes', 'DESC'),
            default => $qb->orderBy('h.date_irrigation', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }
}
