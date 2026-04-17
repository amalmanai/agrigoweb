<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\ProduitComment;
use App\Entity\User;
use App\Repository\ProduitRepository;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id_produit = null;

    #[ORM\OneToMany(targetEntity: ProduitComment::class, mappedBy: 'produit', orphanRemoval: true, cascade: ['persist'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId_produit(): ?int
    {
        return $this->id_produit;
    }

    public function setId_produit(int $id_produit): static
    {
        $this->id_produit = $id_produit;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
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

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'La catégorie doit contenir au moins {{ limit }} caractères.', maxMessage: 'La catégorie ne peut pas dépasser {{ limit }} caractères.')]
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
    #[Assert\NotNull(message: 'La quantité disponible est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité disponible doit être positive ou nulle.')]
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

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'L\'unité est obligatoire.')]
    #[Assert\Length(min: 1, max: 50, minMessage: 'L\'unité doit contenir au moins {{ limit }} caractère.', maxMessage: 'L\'unité ne peut pas dépasser {{ limit }} caractères.')]
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
    #[Assert\NotNull(message: 'Le seuil d\'alerte est obligatoire.')]
    #[Assert\Positive(message: 'Le seuil d\'alerte doit être positif.')]
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

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_expiration = null;

    public function getDate_expiration(): ?\DateTimeInterface
    {
        return $this->date_expiration;
    }

    public function setDate_expiration(?\DateTimeInterface $date_expiration): static
    {
        $this->date_expiration = $date_expiration;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotNull(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\Positive(message: 'Le prix unitaire doit être positif.')]
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

    #[ORM\Column(type: 'text', nullable: true)]
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

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(ProduitComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setProduit($this);
        }

        return $this;
    }

    public function removeComment(ProduitComment $comment): static
    {
        $this->comments->removeElement($comment);

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'CASCADE')]
    private ?User $owner = null;

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

}
