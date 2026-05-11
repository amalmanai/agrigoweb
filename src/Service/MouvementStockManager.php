<?php

namespace App\Service;

use App\Entity\MouvementStock;

class MouvementStockManager
{
    public function validate(MouvementStock $mouvement): bool
    {
        if ($mouvement->getQuantite() <= 0) {
            throw new \InvalidArgumentException('La quantité doit être positive');
        }

        if (empty($mouvement->getMotif()) || strlen($mouvement->getMotif()) < 5) {
            throw new \InvalidArgumentException('Le motif est obligatoire et doit contenir au moins 5 caractères');
        }

        return true;
    }
}
