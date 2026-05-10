<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        // Règle 1 : L'email doit être valide
        if (!filter_var($user->getEmailUser(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        // Règle 2 : Le mot de passe doit contenir au moins 8 caractères
        if (strlen((string) $user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }

        return true;
    }
}
