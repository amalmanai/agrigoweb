<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Vente;

/**
 * Service métier responsable de la validation des règles de gestion
 * associées à l'entité Vente.
 *
 * Règles métier validées :
 *  1. La description de la vente est obligatoire (non vide).
 *  2. Le statut est obligatoire et ne peut pas être vide.
 */
class VenteManager
{
    /**
     * Valide les règles métier d'une vente.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(Vente $vente): bool
    {
        // Règle 1 : la description est obligatoire
        if (empty($vente->getDescription())) {
            throw new \InvalidArgumentException('La description de la vente est obligatoire');
        }

        // Règle 2 : le statut est obligatoire
        if (empty($vente->getStatus())) {
            throw new \InvalidArgumentException('Le statut de la vente est obligatoire');
        }

        return true;
    }
}
