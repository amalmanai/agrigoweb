<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Incrémente le compteur de violations « mots interdits » pour un utilisateur (persisté en base).
 */
final class BadWordStrikeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return int nouveau nombre de strikes après incrément (0 si utilisateur introuvable)
     */
    public function incrementStrikesForUser(User $user): int
    {
        $id = $user->getIdUser();
        if ($id === null) {
            return 0;
        }

        $managed = $this->entityManager->find(User::class, $id);
        if ($managed === null) {
            return 0;
        }

        $managed->incrementBadWordCommentStrikes();
        $strikes = $managed->getBadWordCommentStrikes();
        if ($strikes >= 3) {
            $managed->setIsActive(false);
        }

        $this->entityManager->flush();

        return $strikes;
    }
}
