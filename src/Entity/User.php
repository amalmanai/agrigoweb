<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\ProduitComment;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user')]
    private ?int $idUser = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    private ?string $nomUser = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide.")]
    private ?string $prenomUser = null;

    #[ORM\Column(name: 'email_user', length: 255, unique: true)]
    #[Assert\NotBlank(message: "L'adresse email ne peut pas être vide.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
    private ?string $emailUser = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $roleUser = 'ROLE_USER';

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(pattern: "/^[0-9]{8}$/", message: "Le numéro doit contenir exactement 8 chiffres.")]
    private ?int $numUser = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse ne peut pas être vide.")]
    private ?string $adresseUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPath = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'reset_token', length: 20, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_expires', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetExpiresAt = null;

    #[ORM\OneToMany(targetEntity: Parcelle::class, mappedBy: 'owner')]
    private Collection $parcelles;

    #[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'owner')]
    private Collection $cultures;

    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'owner')]
    private Collection $produits;

    #[ORM\OneToMany(targetEntity: ProduitComment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $produitCommentaires;

    public function __construct()
    {
        $this->parcelles = new ArrayCollection();
        $this->cultures = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->produitCommentaires = new ArrayCollection();
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function getNomUser(): ?string
    {
        return $this->nomUser;
    }

    public function setNomUser(string $nomUser): static
    {
        $this->nomUser = $nomUser;
        return $this;
    }

    public function getPrenomUser(): ?string
    {
        return $this->prenomUser;
    }

    public function setPrenomUser(string $prenomUser): static
    {
        $this->prenomUser = $prenomUser;
        return $this;
    }

    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    public function setEmailUser(string $emailUser): static
    {
        $this->emailUser = $emailUser;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoleUser(): ?string
    {
        return $this->roleUser;
    }

    public function setRoleUser(string $roleUser): static
    {
        $this->roleUser = $roleUser;
        return $this;
    }

    public function getNumUser(): ?int
    {
        return $this->numUser;
    }

    public function setNumUser(int $numUser): static
    {
        $this->numUser = $numUser;
        return $this;
    }

    public function getAdresseUser(): ?string
    {
        return $this->adresseUser;
    }

    public function setAdresseUser(string $adresseUser): static
    {
        $this->adresseUser = $adresseUser;
        return $this;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): static
    {
        $this->photoPath = $photoPath;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    // Symfony Security Interface Methods
    public function getRoles(): array
    {
        $storedRole = strtoupper(trim((string) $this->roleUser));

        // Support legacy values from database like "admin" or business labels.
        if (str_contains($storedRole, 'ADMIN')) {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }

        if (str_starts_with($storedRole, 'ROLE_')) {
            return array_unique([$storedRole, 'ROLE_USER']);
        }

        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->emailUser;
    }

    public function getFullName(): string
    {
        return trim($this->prenomUser . ' ' . $this->nomUser);
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetExpiresAt;
    }

    public function setResetExpiresAt(?\DateTimeImmutable $resetExpiresAt): self
    {
        $this->resetExpiresAt = $resetExpiresAt;
        return $this;
    }

    public function isResetTokenValid(): bool
    {
        return $this->resetToken !== null &&
            $this->resetExpiresAt !== null &&
            $this->resetExpiresAt > new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Parcelle>
     */
    public function getParcelles(): Collection
    {
        return $this->parcelles;
    }

    /**
     * @return Collection<int, Culture>
     */
    public function getCultures(): Collection
    {
        return $this->cultures;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    /**
     * @return Collection<int, ProduitComment>
     */
    public function getProduitCommentaires(): Collection
    {
        return $this->produitCommentaires;
    }
}
