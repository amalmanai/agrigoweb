<?php

namespace App\Repository;

use App\Entity\Parcelle;
use App\Entity\SystemeIrrigation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemeIrrigation>
 */
class SystemeIrrigationRepository extends ServiceEntityRepository
{
    /** @var list<string> */
    private const TRIS_VALIDES = [
        'nom_asc', 'nom_desc',
        'date_asc', 'date_desc',
        'parcelle_asc', 'parcelle_desc',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemeIrrigation::class);
    }

    /**
     * Jointure sur parcelles pour exclure les id_parcelle invalides + recherche / tri.
     *
     * @return list<SystemeIrrigation>
     */
    public function findAllWithExistingParcelle(?string $recherche = null, string $tri = 'nom_asc', ?User $parcelleOwner = null): array
    {
        if (!\in_array($tri, self::TRIS_VALIDES, true)) {
            $tri = 'nom_asc';
        }

        $qb = $this->createQueryBuilder('s')
            ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = s.id_parcelle');

        if ($parcelleOwner instanceof User) {
            $qb->andWhere('p.owner = :owner')->setParameter('owner', $parcelleOwner);
        }

        if (null !== $recherche && '' !== trim($recherche)) {
            $term = '%'.\mb_strtolower(trim($recherche)).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(s.nom_systeme) LIKE :rq',
                'LOWER(p.nomParcelle) LIKE :rq'
            ))->setParameter('rq', $term);
        }

        match ($tri) {
            'nom_desc' => $qb->orderBy('s.nom_systeme', 'DESC'),
            'date_asc' => $qb->orderBy('s.date_creation', 'ASC'),
            'date_desc' => $qb->orderBy('s.date_creation', 'DESC'),
            'parcelle_asc' => $qb->orderBy('p.nomParcelle', 'ASC'),
            'parcelle_desc' => $qb->orderBy('p.nomParcelle', 'DESC'),
            default => $qb->orderBy('s.nom_systeme', 'ASC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function findOneWithExistingParcelle(int $idSysteme, ?User $parcelleOwner = null): ?SystemeIrrigation
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = s.id_parcelle')
            ->andWhere('s.id_systeme = :id')
            ->setParameter('id', $idSysteme);

        if ($parcelleOwner instanceof User) {
            $qb->andWhere('p.owner = :owner')->setParameter('owner', $parcelleOwner);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return list<SystemeIrrigation>
     */
    public function findActiveByOwner(User $owner): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = s.id_parcelle')
            ->andWhere('p.owner = :owner')
            ->andWhere('UPPER(COALESCE(s.statut, :empty)) = :actif')
            ->setParameter('owner', $owner)
            ->setParameter('empty', '')
            ->setParameter('actif', 'ACTIF')
            ->orderBy('s.nom_systeme', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
