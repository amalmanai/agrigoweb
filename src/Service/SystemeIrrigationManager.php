<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SystemeIrrigation;

/**
 * Service métier responsable de la validation des règles de gestion
 * associées à l'entité SystemeIrrigation.
 *
 * Règles métier validées :
 *  1. Le nom du système d'irrigation est obligatoire (non vide).
 *  2. Le statut doit appartenir aux valeurs autorisées.
 */
class SystemeIrrigationManager
{
    /** @var string[] */
    private const STATUTS_AUTORISES = [
        'Actif',
        'Inactif',
        'En maintenance',
    ];

    /**
     * Valide les règles métier d'un système d'irrigation.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(SystemeIrrigation $systeme): bool
    {
        // Règle 1 : le nom est obligatoire
        if (empty($systeme->getNomSysteme())) {
            throw new \InvalidArgumentException('Le nom du système est obligatoire');
        }

        // Règle 2 : le statut doit être une valeur autorisée
        $statut = $systeme->getStatut();
        if ($statut === null || !in_array($statut, self::STATUTS_AUTORISES, true)) {
            throw new \InvalidArgumentException('Le statut du système est invalide');
        }

        return true;
    }
}
