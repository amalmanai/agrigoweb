<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Vente;
use App\Service\VenteManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service VenteManager.
 *
 * Vérifie les règles métier suivantes :
 *  1. La description de la vente est obligatoire.
 *  2. Le statut de la vente est obligatoire.
 */
class VenteManagerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Test 1 : Vente valide                                              //
    // ------------------------------------------------------------------ //

    public function testVenteValide(): void
    {
        $vente = new Vente();
        $vente->setDescription('Lot de tomates biologiques');
        $vente->setStatus('En cours');

        $manager = new VenteManager();

        $this->assertTrue($manager->validate($vente));
    }

    // ------------------------------------------------------------------ //
    //  Test 2 : Description obligatoire — doit lever une exception        //
    // ------------------------------------------------------------------ //

    public function testVenteSansDescriptionLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description de la vente est obligatoire');

        $vente = new Vente();
        // description non renseignée — getDescription() retourne null
        $vente->setStatus('Terminée');

        $manager = new VenteManager();
        $manager->validate($vente);
    }

    // ------------------------------------------------------------------ //
    //  Test 3 : Statut obligatoire — doit lever une exception             //
    // ------------------------------------------------------------------ //

    public function testVenteSansStatutLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut de la vente est obligatoire');

        $vente = new Vente();
        $vente->setDescription('Sacs de pommes de terre');
        // statut non renseigné — getStatus() retourne null

        $manager = new VenteManager();
        $manager->validate($vente);
    }
}
