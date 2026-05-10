<?php

namespace App\Tests\Service;

use App\Entity\MouvementStock;
use App\Service\MouvementStockManager;
use PHPUnit\Framework\TestCase;

class MouvementStockManagerTest extends TestCase
{
    public function testValidMouvementStock(): void
    {
        $mouvement = new MouvementStock();
        $mouvement->setQuantite(50);
        $mouvement->setMotif('Achat de semences');

        $manager = new MouvementStockManager();
        $this->assertTrue($manager->validate($mouvement));
    }

    public function testMouvementWithZeroOrNegativeQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $mouvement = new MouvementStock();
        $mouvement->setQuantite(-10);
        $mouvement->setMotif('Achat de semences');

        $manager = new MouvementStockManager();
        $manager->validate($mouvement);
    }

    public function testMouvementWithShortMotif(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $mouvement = new MouvementStock();
        $mouvement->setQuantite(50);
        $mouvement->setMotif('abc');

        $manager = new MouvementStockManager();
        $manager->validate($mouvement);
    }
}
