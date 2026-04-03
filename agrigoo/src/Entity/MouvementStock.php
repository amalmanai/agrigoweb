<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\MouvementStockRepository;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
#[ORM\Table(name: 'mouvement_stock')]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_mouvement = null;

    public function getId_mouvement(): ?int
    {
        return $this->id_mouvement;
    }

    public function setId_mouvement(int $id_mouvement): static
    {
        $this->id_mouvement = $id_mouvement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_mouvement = null;

    public function getType_mouvement(): ?string
    {
        return $this->type_mouvement;
    }

    public function setType_mouvement(string $type_mouvement): static
    {
        $this->type_mouvement = $type_mouvement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_mouvement = null;

    public function getDate_mouvement(): ?string
    {
        return $this->date_mouvement;
    }

    public function setDate_mouvement(string $date_mouvement): static
    {
        $this->date_mouvement = $date_mouvement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $quantite = null;

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $motif = null;

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_user = null;

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(int $id_user): static
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getIdMouvement(): ?int
    {
        return $this->id_mouvement;
    }

    public function getTypeMouvement(): ?string
    {
        return $this->type_mouvement;
    }

    public function setTypeMouvement(string $type_mouvement): static
    {
        $this->type_mouvement = $type_mouvement;

        return $this;
    }

    public function getDateMouvement(): ?string
    {
        return $this->date_mouvement;
    }

    public function setDateMouvement(string $date_mouvement): static
    {
        $this->date_mouvement = $date_mouvement;

        return $this;
    }

    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function setIdProduit(int $id_produit): static
    {
        $this->id_produit = $id_produit;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

}
