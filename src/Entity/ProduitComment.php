<?php

namespace App\Entity;

use App\Entity\Produit;
use App\Entity\User;
use App\Repository\ProduitCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitCommentRepository::class)]
#[ORM\Table(name: 'produit_commentaire')]
class ProduitComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commentaire', type: 'integer')]
    private ?int $id_commentaire = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide.')]
    private ?string $contenu = null;

    #[ORM\Column(name: 'date_commentaire', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $date_commentaire = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'produitCommentaires')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->date_commentaire = new \DateTimeImmutable();
    }

    public function getIdCommentaire(): ?int
    {
        return $this->id_commentaire;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateCommentaire(): ?\DateTimeImmutable
    {
        return $this->date_commentaire;
    }

    public function setDateCommentaire(\DateTimeImmutable $date_commentaire): static
    {
        $this->date_commentaire = $date_commentaire;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
