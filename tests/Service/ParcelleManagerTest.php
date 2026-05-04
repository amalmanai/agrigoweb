<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Parcelle;
use App\Service\ParcelleManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service ParcelleManager.
 *
 * Vérifie les règles métier suivantes :
 *  1. Le nom de la parcelle est obligatoire.
 *  2. La surface doit être strictement positive.
 */
class ParcelleManagerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Test 1 : Parcelle valide                                           //
    // ------------------------------------------------------------------ //

    public function testParcelleValide(): void
    {
        $parcelle = new Parcelle();
        $parcelle->setNomParcelle('Champ Nord');
        $parcelle->setSurface(15.5);

        $manager = new ParcelleManager();

        $this->assertTrue($manager->validate($parcelle));
    }

    // ------------------------------------------------------------------ //
    //  Test 2 : Nom obligatoire — doit lever une exception                //
    // ------------------------------------------------------------------ //

    public function testParcelleSansNomLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la parcelle est obligatoire');

        $parcelle = new Parcelle();
        // nom non renseigné — getNomParcelle() retourne null
        $parcelle->setSurface(10.0);

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }

    // ------------------------------------------------------------------ //
    //  Test 3 : Surface nulle ou négative — doit lever une exception      //
    // ------------------------------------------------------------------ //

    public function testParcelleSurfaceNegativeLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La surface doit être strictement positive');

        $parcelle = new Parcelle();
        $parcelle->setNomParcelle('Champ Sud');
        $parcelle->setSurface(-5.0); // surface invalide

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }
}
