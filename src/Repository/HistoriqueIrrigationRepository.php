<?php

namespace App\Repository;

use App\Entity\HistoriqueIrrigation;
use App\Entity\Parcelle;
use App\Entity\SystemeIrrigation;
use App\Entity\User;
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
    public function findAllFiltered(?string $rechercheNomSysteme = null, string $tri = 'date_desc', ?User $parcelleOwner = null): array
    {
        if (!\in_array($tri, self::TRIS_VALIDES, true)) {
            $tri = 'date_desc';
        }

        $qb = $this->createQueryBuilder('h')
            ->innerJoin('h.systemeIrrigation', 'sys')->addSelect('sys')
            ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = sys.id_parcelle');

        if ($parcelleOwner instanceof User) {
            $qb->andWhere('p.owner = :owner')->setParameter('owner', $parcelleOwner);
        }

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

    /**
     * @return list<HistoriqueIrrigation>
     */
    public function findByOwnerAndPeriod(User $owner, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.systemeIrrigation', 'sys')->addSelect('sys')
            ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = sys.id_parcelle')->addSelect('p')
            ->andWhere('p.owner = :owner')
            ->andWhere('h.date_irrigation BETWEEN :from AND :to')
            ->setParameter('owner', $owner)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('h.date_irrigation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestForSysteme(SystemeIrrigation $systeme): ?HistoriqueIrrigation
    {
        return $this->findOneBy([
            'systemeIrrigation' => $systeme,
        ], [
            'date_irrigation' => 'DESC',
        ]);
    }
}
