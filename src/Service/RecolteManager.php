<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recolte;

/**
 * Service métier responsable de la validation des règles de gestion
 * associées à l'entité Recolte.
 *
 * Règles métier validées :
 *  1. Le nom du produit récolté est obligatoire (non vide).
 *  2. La quantité récoltée doit être strictement positive (> 0).
 */
class RecolteManager
{
    /**
     * Valide les règles métier d'une récolte.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(Recolte $recolte): bool
    {
        // Règle 1 : le nom du produit est obligatoire
        if (empty($recolte->getName())) {
            throw new \InvalidArgumentException('Le nom du produit récolté est obligatoire');
        }

        // Règle 2 : la quantité doit être strictement positive
        $quantity = $recolte->getQuantity();
        if ($quantity === null || $quantity <= 0) {
            throw new \InvalidArgumentException('La quantité récoltée doit être strictement positive');
        }

        return true;
    }
}
