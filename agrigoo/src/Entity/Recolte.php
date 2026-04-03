<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\RecolteRepository;

#[ORM\Entity(repositoryClass: RecolteRepository::class)]
#[ORM\Table(name: 'recolte')]
class Recolte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_recolte = null;

    public function getId_recolte(): ?int
    {
        return $this->id_recolte;
    }

    public function setId_recolte(int $id_recolte): static
    {
        $this->id_recolte = $id_recolte;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nom_produit = null;

    public function getNom_produit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNom_produit(?string $nom_produit): static
    {
        $this->nom_produit = $nom_produit;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $quantite = null;

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(?float $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $unite = null;

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): static
    {
        $this->unite = $unite;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_recolte = null;

    public function getDate_recolte(): ?\DateTimeInterface
    {
        return $this->date_recolte;
    }

    public function setDate_recolte(?\DateTimeInterface $date_recolte): static
    {
        $this->date_recolte = $date_recolte;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $cout_production = null;

    public function getCout_production(): ?float
    {
        return $this->cout_production;
    }

    public function setCout_production(?float $cout_production): static
    {
        $this->cout_production = $cout_production;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_user = null;

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(?int $id_user): static
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getIdRecolte(): ?int
    {
        return $this->id_recolte;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(?string $nom_produit): static
    {
        $this->nom_produit = $nom_produit;

        return $this;
    }

    public function getDateRecolte(): ?\DateTime
    {
        return $this->date_recolte;
    }

    public function setDateRecolte(?\DateTime $date_recolte): static
    {
        $this->date_recolte = $date_recolte;

        return $this;
    }

    public function getCoutProduction(): ?string
    {
        return $this->cout_production;
    }

    public function setCoutProduction(?string $cout_production): static
    {
        $this->cout_production = $cout_production;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(?int $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

}
