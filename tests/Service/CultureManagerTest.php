<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Culture;
use App\Service\CultureManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service CultureManager.
 *
 * Vérifie les règles métier suivantes :
 *  1. Le nom de la culture est obligatoire.
 *  2. L'état de croissance doit appartenir à la liste autorisée.
 */
class CultureManagerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Test 1 : Culture valide                                            //
    // ------------------------------------------------------------------ //

    public function testCultureValide(): void
    {
        $culture = new Culture();
        $culture->setNomCulture('Blé dur');
        $culture->setEtatCroissance('Croissance');

        $manager = new CultureManager();

        $this->assertTrue($manager->validate($culture));
    }

    // ------------------------------------------------------------------ //
    //  Test 2 : Nom obligatoire — doit lever une exception                //
    // ------------------------------------------------------------------ //

    public function testCultureSansNomLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la culture est obligatoire');

        $culture = new Culture();
        // nom non renseigné — getNomCulture() retourne null
        $culture->setEtatCroissance('Semis');

        $manager = new CultureManager();
        $manager->validate($culture);
    }

    // ------------------------------------------------------------------ //
    //  Test 3 : État de croissance invalide — doit lever une exception    //
    // ------------------------------------------------------------------ //

    public function testCultureAvecEtatInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'état de croissance est invalide');

        $culture = new Culture();
        $culture->setNomCulture('Tomate cerise');
        $culture->setEtatCroissance('Inconnu'); // valeur hors liste

        $manager = new CultureManager();
        $manager->validate($culture);
    }
}
