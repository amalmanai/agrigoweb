<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Parcelle;

/**
 * Service métier responsable de la validation des règles de gestion
 * associées à l'entité Parcelle.
 *
 * Règles métier validées :
 *  1. Le nom de la parcelle est obligatoire (non vide).
 *  2. La surface doit être strictement positive (> 0).
 */
class ParcelleManager
{
    /**
     * Valide les règles métier d'une parcelle.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(Parcelle $parcelle): bool
    {
        // Règle 1 : le nom est obligatoire
        if (empty($parcelle->getNomParcelle())) {
            throw new \InvalidArgumentException('Le nom de la parcelle est obligatoire');
        }

        // Règle 2 : la surface doit être strictement positive
        $surface = $parcelle->getSurface();
        if ($surface === null || $surface <= 0) {
            throw new \InvalidArgumentException('La surface doit être strictement positive');
        }

        return true;
    }
}
