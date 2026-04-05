<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ParcelleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParcelleRepository::class)]
#[ORM\Table(name: 'parcelles')]
class Parcelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_parcelle', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_parcelle', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la parcelle est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom de la parcelle doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le nom de la parcelle ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $nomParcelle = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: 'La surface est obligatoire.')]
    #[Assert\Positive(message: 'La surface doit etre strictement positive.')]
    private ?float $surface = null;

    #[ORM\Column(name: 'coordonnees_gps', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Les coordonnees GPS sont obligatoires.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Les coordonnees GPS ne doivent pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$/',
        message: 'Le format GPS est invalide. Utilisez: latitude, longitude.'
    )]
    private ?string $coordonneesGps = null;

    #[ORM\Column(name: 'type_sol', type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le type de sol est obligatoire.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le type de sol ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $typeSol = null;

    #[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'parcelle')]
    private Collection $cultures;

    #[ORM\OneToMany(targetEntity: HistoriqueCulture::class, mappedBy: 'parcelle')]
    private Collection $historiqueCultures;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'parcelles')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    public function __construct()
    {
        $this->cultures = new ArrayCollection();
        $this->historiqueCultures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomParcelle(): ?string
    {
        return $this->nomParcelle;
    }

    public function setNomParcelle(?string $nomParcelle): self
    {
        $this->nomParcelle = $nomParcelle;

        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): self
    {
        $this->surface = $surface;

        return $this;
    }

    public function getCoordonneesGps(): ?string
    {
        return $this->coordonneesGps;
    }

    public function setCoordonneesGps(?string $coordonneesGps): self
    {
        $this->coordonneesGps = $coordonneesGps;

        return $this;
    }

    public function getTypeSol(): ?string
    {
        return $this->typeSol;
    }

    public function setTypeSol(?string $typeSol): self
    {
        $this->typeSol = $typeSol;

        return $this;
    }

    /**
     * @return Collection<int, Culture>
     */
    public function getCultures(): Collection
    {
        return $this->cultures;
    }

    public function addCulture(Culture $culture): self
    {
        if (!$this->cultures->contains($culture)) {
            $this->cultures->add($culture);
            $culture->setParcelle($this);
        }

        return $this;
    }

    public function removeCulture(Culture $culture): self
    {
        if ($this->cultures->removeElement($culture)) {
            if ($culture->getParcelle() === $this) {
                $culture->setParcelle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HistoriqueCulture>
     */
    public function getHistoriqueCultures(): Collection
    {
        return $this->historiqueCultures;
    }

    public function addHistoriqueCulture(HistoriqueCulture $historiqueCulture): self
    {
        if (!$this->historiqueCultures->contains($historiqueCulture)) {
            $this->historiqueCultures->add($historiqueCulture);
            $historiqueCulture->setParcelle($this);
        }

        return $this;
    }

    public function removeHistoriqueCulture(HistoriqueCulture $historiqueCulture): self
    {
        if ($this->historiqueCultures->removeElement($historiqueCulture)) {
            if ($historiqueCulture->getParcelle() === $this) {
                $historiqueCulture->setParcelle(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nomParcelle ?? (string) $this->id;
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
}
