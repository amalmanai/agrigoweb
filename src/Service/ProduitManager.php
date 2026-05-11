<?php

namespace App\Service;

use App\Entity\Produit;

class ProduitManager
{
    public function validate(Produit $produit): bool
    {
        if (empty($produit->getNomProduit())) {
            throw new \InvalidArgumentException('Le nom du produit est obligatoire');
        }

        if ($produit->getQuantiteDisponible() < 0) {
            throw new \InvalidArgumentException('La quantité ne peut pas être négative');
        }

        if ($produit->getPrixUnitaire() <= 0) {
            throw new \InvalidArgumentException('Le prix d\'un article doit toujours être supérieur à zéro');
        }

        return true;
    }
}
