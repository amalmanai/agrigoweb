<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CultureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: CultureRepository::class)]
#[ORM\Table(name: 'cultures')]
#[Vich\Uploadable]
class Culture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_culture', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_culture', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la culture est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 80,
        minMessage: 'Le nom de la culture doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le nom de la culture ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: "/^[\\p{L}][\\p{L}\\s'\\-]{1,79}$/u",
        message: 'Le nom doit commencer par une lettre et contenir uniquement des lettres, espaces, apostrophes ou tirets.'
    )]
    private ?string $nomCulture = null;

    #[ORM\Column(name: 'date_semis', type: 'date')]
    #[Assert\NotNull(message: 'La date de semis est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date de semis est invalide.')]
    private ?\DateTimeInterface $dateSemis = null;

    #[ORM\Column(name: 'etat_croissance', type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'L\'etat de croissance est obligatoire.')]
    #[Assert\Choice(
        choices: ['Semis', 'Croissance', 'Floraison', 'Recolte', 'Recolte termine'],
        message: 'L\'etat de croissance selectionne est invalide.'
    )]
    private ?string $etatCroissance = null;

    #[ORM\Column(name: 'rendement_prevu', type: 'float', nullable: true)]
    #[Assert\NotNull(message: 'Le rendement prevu est obligatoire.')]
    #[Assert\Type(type: 'float', message: 'Le rendement prevu doit etre numerique.')]
    #[Assert\Range(
        min: 0,
        max: 1000000,
        notInRangeMessage: 'Le rendement prevu doit etre compris entre {{ min }} et {{ max }}.'
    )]
    private ?float $rendementPrevu = null;

    #[ORM\Column(name: 'date_recolte_estimee', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateRecolteEstimee = null;

    #[ORM\Column(name: 'rendement_estime', type: 'float', nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 1000000,
        notInRangeMessage: 'Le rendement estime doit etre compris entre {{ min }} et {{ max }}.'
    )]
    private ?float $rendementEstime = null;

    #[Vich\UploadableField(mapping: 'culture_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(name: 'image_name', type: 'string', length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Parcelle::class, inversedBy: 'cultures')]
    #[ORM\JoinColumn(name: 'id_parcelle', referencedColumnName: 'id_parcelle', nullable: true, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La parcelle est obligatoire.')]
    private ?Parcelle $parcelle = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cultures')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\OneToMany(targetEntity: AlerteRisque::class, mappedBy: 'culture')]
    private Collection $alertesRisques;

    public function __construct()
    {
        $this->alertesRisques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomCulture(): ?string
    {
        return $this->nomCulture;
    }

    public function setNomCulture(string $nomCulture): self
    {
        $this->nomCulture = $nomCulture;

        return $this;
    }

    public function getDateSemis(): ?\DateTimeInterface
    {
        return $this->dateSemis;
    }

    public function setDateSemis(\DateTimeInterface $dateSemis): self
    {
        $this->dateSemis = $dateSemis;

        return $this;
    }

    public function getEtatCroissance(): ?string
    {
        return $this->etatCroissance;
    }

    public function setEtatCroissance(?string $etatCroissance): self
    {
        $this->etatCroissance = $etatCroissance;

        return $this;
    }

    public function getRendementPrevu(): ?float
    {
        return $this->rendementPrevu;
    }

    public function setRendementPrevu(?float $rendementPrevu): self
    {
        $this->rendementPrevu = $rendementPrevu;

        return $this;
    }

    public function getDateRecolteEstimee(): ?\DateTimeImmutable
    {
        return $this->dateRecolteEstimee;
    }

    public function setDateRecolteEstimee(?\DateTimeImmutable $dateRecolteEstimee): self
    {
        $this->dateRecolteEstimee = $dateRecolteEstimee;

        return $this;
    }

    public function getRendementEstime(): ?float
    {
        return $this->rendementEstime;
    }

    public function setRendementEstime(?float $rendementEstime): self
    {
        $this->rendementEstime = $rendementEstime;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): self
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            $this->updatedAt = new \DateTimeImmutable('now');
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getParcelle(): ?Parcelle
    {
        return $this->parcelle;
    }

    public function setParcelle(?Parcelle $parcelle): self
    {
        $this->parcelle = $parcelle;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, AlerteRisque>
     */
    public function getAlertesRisques(): Collection
    {
        return $this->alertesRisques;
    }

    public function addAlertesRisque(AlerteRisque $alerteRisque): self
    {
        if (!$this->alertesRisques->contains($alerteRisque)) {
            $this->alertesRisques->add($alerteRisque);
            $alerteRisque->setCulture($this);
        }

        return $this;
    }

    public function removeAlertesRisque(AlerteRisque $alerteRisque): self
    {
        if ($this->alertesRisques->removeElement($alerteRisque)) {
            if ($alerteRisque->getCulture() === $this) {
                $alerteRisque->setCulture(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nomCulture ?? (string) $this->id;
    }
}
