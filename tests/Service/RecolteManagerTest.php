<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Recolte;
use App\Service\RecolteManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service RecolteManager.
 *
 * Vérifie les règles métier suivantes :
 *  1. Le nom du produit récolté est obligatoire.
 *  2. La quantité récoltée doit être strictement positive.
 */
class RecolteManagerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Test 1 : Récolte valide                                            //
    // ------------------------------------------------------------------ //

    public function testRecolteValide(): void
    {
        $recolte = new Recolte();
        $recolte->setName('Blé tendre');
        $recolte->setQuantity(250.5);

        $manager = new RecolteManager();

        $this->assertTrue($manager->validate($recolte));
    }

    // ------------------------------------------------------------------ //
    //  Test 2 : Nom obligatoire — doit lever une exception                //
    // ------------------------------------------------------------------ //

    public function testRecolteSansNomLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit récolté est obligatoire');

        $recolte = new Recolte();
        // nom non renseigné — getName() retourne null
        $recolte->setQuantity(100.0);

        $manager = new RecolteManager();
        $manager->validate($recolte);
    }

    // ------------------------------------------------------------------ //
    //  Test 3 : Quantité nulle ou négative — doit lever une exception     //
    // ------------------------------------------------------------------ //

    public function testRecolteQuantiteNegativeLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantité récoltée doit être strictement positive');

        $recolte = new Recolte();
        $recolte->setName('Maïs');
        $recolte->setQuantity(-20.0); // quantité invalide

        $manager = new RecolteManager();
        $manager->validate($recolte);
    }
}
