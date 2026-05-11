<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Culture;

/**
 * Service métier responsable de la validation des règles de gestion
 * associées à l'entité Culture.
 *
 * Règles métier validées :
 *  1. Le nom de la culture est obligatoire (non vide).
 *  2. L'état de croissance doit appartenir à la liste autorisée.
 */
class CultureManager
{
    /** @var string[] */
    private const ETATS_AUTORISES = [
        'Semis',
        'Croissance',
        'Floraison',
        'Recolte',
        'Recolte termine',
    ];

    /**
     * Valide les règles métier d'une culture.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(Culture $culture): bool
    {
        // Règle 1 : le nom est obligatoire
        if (empty($culture->getNomCulture())) {
            throw new \InvalidArgumentException('Le nom de la culture est obligatoire');
        }

        // Règle 2 : l'état de croissance doit être valide
        $etat = $culture->getEtatCroissance();
        if ($etat === null || !in_array($etat, self::ETATS_AUTORISES, true)) {
            throw new \InvalidArgumentException('L\'état de croissance est invalide');
        }

        return true;
    }
}
