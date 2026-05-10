<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user', nullable: true)]
    private ?int $idUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    private ?string $nomUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide.")]
    private ?string $prenomUser = null;

    #[ORM\Column(name: 'email_user', length: 255, unique: true, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse email ne peut pas être vide.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
    private ?string $emailUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $roleUser = 'ROLE_USER';

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(pattern: "/^[0-9]{8}$/", message: "Le numéro doit contenir exactement 8 chiffres.")]
    private ?int $numUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse ne peut pas être vide.")]
    private ?string $adresseUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPath = null;
    #[Ignore]
    #[Vich\UploadableField(mapping: 'user_photos', fileNameProperty: 'photoPath')]
    private ?File $photoFile = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'bad_word_comment_strikes', type: 'integer', options: ['default' => 0])]
    private int $badWordCommentStrikes = 0;

    #[ORM\Column(name: 'reset_token', length: 20, nullable: true)]
    #[Ignore]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_expires', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Ignore]
    private ?string $loginToken = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceDescriptor = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hostedDomain = null;

    #[ORM\OneToMany(targetEntity: Parcelle::class, mappedBy: 'owner')]
    private Collection $parcelles;

    #[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'owner')]
    private Collection $cultures;

    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'owner')]
    private Collection $produits;

    #[ORM\OneToMany(targetEntity: ProduitComment::class, mappedBy: 'user')]
    private Collection $produitCommentaires;

    public function __construct()
    {
        $this->parcelles = new ArrayCollection();
        $this->cultures = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->produitCommentaires = new ArrayCollection();
    }
    public function __serialize(): array
    {
        return [
            'idUser' => $this->idUser,
            'emailUser' => $this->emailUser,
            'password' => $this->password,
            'roleUser' => $this->roleUser,
            'isActive' => $this->isActive,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->idUser = $data['idUser'];
        $this->emailUser = $data['emailUser'];
        $this->password = $data['password'];
        $this->roleUser = $data['roleUser'];
        $this->isActive = $data['isActive'];
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

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $photoFile
     */
    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;

        if (null !== $photoFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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

    public function getBadWordCommentStrikes(): int
    {
        return $this->badWordCommentStrikes;
    }

    public function setBadWordCommentStrikes(int $badWordCommentStrikes): static
    {
        $this->badWordCommentStrikes = max(0, $badWordCommentStrikes);
        return $this;
    }

    public function incrementBadWordCommentStrikes(): static
    {
        ++$this->badWordCommentStrikes;
        return $this;
    }

    public function resetBadWordCommentStrikes(): static
    {
        $this->badWordCommentStrikes = 0;
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

    public function getLoginToken(): ?string
    {
        return $this->loginToken;
    }

    public function setLoginToken(?string $loginToken): static
    {
        $this->loginToken = $loginToken;
        return $this;
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function setFaceDescriptor(?string $faceDescriptor): static
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getHostedDomain(): ?string
    {
        return $this->hostedDomain;
    }

    public function setHostedDomain(?string $hostedDomain): static
    {
        $this->hostedDomain = $hostedDomain;
        return $this;
    }
}
