<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ProduitRepository;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_produit = null;

    public function getId_produit(): ?int
    {
        return $this->id_produit;
    }

    public function setId_produit(int $id_produit): static
    {
        $this->id_produit = $id_produit;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_produit = null;

    public function getNom_produit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNom_produit(string $nom_produit): static
    {
        $this->nom_produit = $nom_produit;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $categorie = null;

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $quantite_disponible = null;

    public function getQuantite_disponible(): ?int
    {
        return $this->quantite_disponible;
    }

    public function setQuantite_disponible(int $quantite_disponible): static
    {
        $this->quantite_disponible = $quantite_disponible;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $unite = null;

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): static
    {
        $this->unite = $unite;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $seuil_alerte = null;

    public function getSeuil_alerte(): ?int
    {
        return $this->seuil_alerte;
    }

    public function setSeuil_alerte(int $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $date_expiration = null;

    public function getDate_expiration(): ?string
    {
        return $this->date_expiration;
    }

    public function setDate_expiration(?string $date_expiration): static
    {
        $this->date_expiration = $date_expiration;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $prix_unitaire = null;

    public function getPrix_unitaire(): ?int
    {
        return $this->prix_unitaire;
    }

    public function setPrix_unitaire(int $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;
        return $this;
    }

    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(string $nom_produit): static
    {
        $this->nom_produit = $nom_produit;

        return $this;
    }

    public function getQuantiteDisponible(): ?int
    {
        return $this->quantite_disponible;
    }

    public function setQuantiteDisponible(int $quantite_disponible): static
    {
        $this->quantite_disponible = $quantite_disponible;

        return $this;
    }

    public function getSeuilAlerte(): ?int
    {
        return $this->seuil_alerte;
    }

    public function setSeuilAlerte(int $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;

        return $this;
    }

    public function getDateExpiration(): ?string
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(?string $date_expiration): static
    {
        $this->date_expiration = $date_expiration;

        return $this;
    }

    public function getPrixUnitaire(): ?int
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(int $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;

        return $this;
    }

}
