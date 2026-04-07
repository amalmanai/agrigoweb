<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Parcelle;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ParcelleToNameTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private User $user
    ) {
    }

    /**
     * Transforms an object (parcelle) to a string (name).
     *
     * @param  Parcelle|null $parcelle
     */
    public function transform($parcelle): string
    {
        if (null === $parcelle) {
            return '';
        }

        return $parcelle->getNomParcelle();
    }

    /**
     * Transforms a string (name) to an object (parcelle).
     *
     * @param  string $parcelleName
     * @throws TransformationFailedException if object (parcelle) is not found.
     */
    public function reverseTransform($parcelleName): ?Parcelle
    {
        if (!$parcelleName) {
            return null;
        }

        $criteria = ['nomParcelle' => $parcelleName];

        // Seul l'ADMIN peut voir les parcelles de tout le monde. 
        // L'utilisateur standard ne peut voir que les siennes.
        if (!in_array('ROLE_ADMIN', $this->user->getRoles(), true)) {
            $criteria['owner'] = $this->user;
        }

        $parcelle = $this->entityManager
            ->getRepository(Parcelle::class)
            ->findOneBy($criteria);

        if (null === $parcelle) {
            throw new TransformationFailedException(sprintf(
                'La parcelle avec le nom "%s" n\'existe pas%s.',
                $parcelleName,
                in_array('ROLE_ADMIN', $this->user->getRoles(), true) ? '' : ' pour votre compte'
            ));
        }

        return $parcelle;
    }
}
