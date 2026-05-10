<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\ProduitManager;
use PHPUnit\Framework\TestCase;

class ProduitManagerTest extends TestCase
{
    public function testValidProduit(): void
    {
        $produit = new Produit();
        $produit->setNomProduit('Tomate');
        $produit->setQuantiteDisponible(100);
        $produit->setPrixUnitaire(5);

        $manager = new ProduitManager();
        $this->assertTrue($manager->validate($produit));
    }

    public function testProduitWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $produit = new Produit();
        $produit->setNomProduit('');
        $produit->setQuantiteDisponible(10);
        $produit->setPrixUnitaire(5);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }

    public function testProduitWithNegativeQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $produit = new Produit();
        $produit->setNomProduit('Tomate');
        $produit->setQuantiteDisponible(-5);
        $produit->setPrixUnitaire(5);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }

    public function testProduitWithZeroOrNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $produit = new Produit();
        $produit->setNomProduit('Tomate');
        $produit->setQuantiteDisponible(10);
        $produit->setPrixUnitaire(0);

        $manager = new ProduitManager();
        $manager->validate($produit);
    }
}
