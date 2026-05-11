<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\SystemeIrrigation;
use App\Service\SystemeIrrigationManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service SystemeIrrigationManager.
 *
 * Vérifie les règles métier suivantes :
 *  1. Le nom du système d'irrigation est obligatoire.
 *  2. Le statut doit appartenir aux valeurs autorisées (Actif / Inactif / En maintenance).
 */
class SystemeIrrigationManagerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Test 1 : Système valide                                            //
    // ------------------------------------------------------------------ //

    public function testSystemeValide(): void
    {
        $systeme = new SystemeIrrigation();
        $systeme->setNomSysteme('Système Goutte-à-Goutte');
        $systeme->setStatut('Actif');

        $manager = new SystemeIrrigationManager();

        $this->assertTrue($manager->validate($systeme));
    }

    // ------------------------------------------------------------------ //
    //  Test 2 : Nom obligatoire — doit lever une exception                //
    // ------------------------------------------------------------------ //

    public function testSystemeSansNomLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du système est obligatoire');

        $systeme = new SystemeIrrigation();
        // nom non renseigné — getNomSysteme() retourne null
        $systeme->setStatut('Actif');

        $manager = new SystemeIrrigationManager();
        $manager->validate($systeme);
    }

    // ------------------------------------------------------------------ //
    //  Test 3 : Statut invalide — doit lever une exception                //
    // ------------------------------------------------------------------ //

    public function testSystemeAvecStatutInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du système est invalide');

        $systeme = new SystemeIrrigation();
        $systeme->setNomSysteme('Système Aspersion');
        $systeme->setStatut('Inconnu'); // valeur hors liste autorisée

        $manager = new SystemeIrrigationManager();
        $manager->validate($systeme);
    }
}
