<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Produit;
use App\Repository\MouvementStockRepository;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotBlank(message: 'La date du mouvement est obligatoire.')]
    private ?\DateTimeInterface $date_mouvement = null;

    public function getDate_mouvement(): ?\DateTimeInterface
    {
        return $this->date_mouvement;
    }

    public function setDate_mouvement(\DateTimeInterface $date_mouvement): static
    {
        $this->date_mouvement = $date_mouvement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être positive.')]
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
    #[Assert\NotBlank(message: 'Le motif est obligatoire.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'Le motif doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le motif ne peut pas dépasser {{ limit }} caractères.')]
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

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', nullable: false)]
    private ?Produit $produit = null;

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(Produit $produit): static
    {
        $this->produit = $produit;
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

    public function getDateMouvement(): ?\DateTimeInterface
    {
        return $this->date_mouvement;
    }

    public function setDateMouvement(\DateTimeInterface $date_mouvement): static
    {
        $this->date_mouvement = $date_mouvement;

        return $this;
    }

    public function getIdProduit(): ?int
    {
        return $this->produit?->getIdProduit();
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

    /** @var string|null Virtual field (not persisted) for comment moderation */
    private ?string $commentaire = null;

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

}
