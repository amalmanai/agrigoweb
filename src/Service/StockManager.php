<?php

namespace App\Service;

use App\Entity\MouvementStock;
use App\Entity\Produit;
use DomainException;
use LogicException;

class StockManager
{
    private StockMailerService $stockMailer;

    public function __construct(StockMailerService $stockMailer)
    {
        $this->stockMailer = $stockMailer;
    }

    public function applyMouvement(MouvementStock $mouvement): void
    {
        $produit = $this->getProduit($mouvement);
        $delta = $this->getDelta($mouvement);
        $this->applyDelta($produit, $delta);
    }

    public function revertMouvement(MouvementStock $mouvement): void
    {
        $produit = $this->getProduit($mouvement);
        $delta = $this->getDelta($mouvement) * -1;
        $this->applyDelta($produit, $delta);
    }

    public function updateMouvement(MouvementStock $updatedMouvement, MouvementStock $originalMouvement): void
    {
        $updatedProduit = $updatedMouvement->getProduit();
        $originalProduit = $originalMouvement->getProduit();

        if ($updatedProduit === null || $originalProduit === null) {
            throw new LogicException('Le produit du mouvement de stock doit être défini.');
        }

        if ($updatedProduit === $originalProduit && $updatedMouvement->getTypeMouvement() === $originalMouvement->getTypeMouvement()) {
            $delta = $this->getDelta($updatedMouvement) - $this->getDelta($originalMouvement);
            $this->applyDelta($updatedProduit, $delta);
            return;
        }

        $this->revertMouvement($originalMouvement);
        $this->applyMouvement($updatedMouvement);
    }

    private function getDelta(MouvementStock $mouvement): int
    {
        $quantite = $mouvement->getQuantite();
        if ($quantite === null) {
            throw new LogicException('La quantité du mouvement est obligatoire.');
        }

        return $mouvement->getTypeMouvement() === 'Entrée' ? $quantite : -$quantite;
    }

    private function applyDelta(Produit $produit, int $delta): void
    {
        $current = $produit->getQuantiteDisponible() ?? 0;
        $nouveau = $current + $delta;

        if ($nouveau < 0) {
            throw new DomainException('Stock insuffisant pour effectuer ce mouvement.');
        }

        $produit->setQuantiteDisponible($nouveau);

        // Alerte si stock élevé (> 200) ou faible (< 20)
        if ($nouveau > 200 || $nouveau < 20) {
            $this->stockMailer->sendStockAlert($produit);
        }
    }

    private function getProduit(MouvementStock $mouvement): Produit
    {
        $produit = $mouvement->getProduit();
        if ($produit === null) {
            throw new LogicException('Aucun produit associé à ce mouvement de stock.');
        }

        return $produit;
    }
}
