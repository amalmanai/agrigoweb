<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ParcellesRepository;

#[ORM\Entity(repositoryClass: ParcellesRepository::class)]
#[ORM\Table(name: 'parcelles')]
class Parcelles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_parcelle = null;

    public function getId_parcelle(): ?int
    {
        return $this->id_parcelle;
    }

    public function setId_parcelle(int $id_parcelle): static
    {
        $this->id_parcelle = $id_parcelle;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_parcelle = null;

    public function getNom_parcelle(): ?string
    {
        return $this->nom_parcelle;
    }

    public function setNom_parcelle(string $nom_parcelle): static
    {
        $this->nom_parcelle = $nom_parcelle;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private ?float $surface = null;

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(float $surface): static
    {
        $this->surface = $surface;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $coordonnees_gps = null;

    public function getCoordonnees_gps(): ?string
    {
        return $this->coordonnees_gps;
    }

    public function setCoordonnees_gps(?string $coordonnees_gps): static
    {
        $this->coordonnees_gps = $coordonnees_gps;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_sol = null;

    public function getType_sol(): ?string
    {
        return $this->type_sol;
    }

    public function setType_sol(?string $type_sol): static
    {
        $this->type_sol = $type_sol;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Cultures::class, mappedBy: 'parcelles')]
    private Collection $culturess;

    /**
     * @return Collection<int, Cultures>
     */
    public function getCulturess(): Collection
    {
        if (!$this->culturess instanceof Collection) {
            $this->culturess = new ArrayCollection();
        }
        return $this->culturess;
    }

    public function addCultures(Cultures $cultures): static
    {
        if (!$this->getCulturess()->contains($cultures)) {
            $this->getCulturess()->add($cultures);
        }
        return $this;
    }

    public function removeCultures(Cultures $cultures): static
    {
        $this->getCulturess()->removeElement($cultures);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: HistoriqueCultures::class, mappedBy: 'parcelles')]
    private Collection $historiqueCulturess;

    public function __construct()
    {
        $this->culturess = new ArrayCollection();
        $this->historiqueCulturess = new ArrayCollection();
    }

    /**
     * @return Collection<int, HistoriqueCultures>
     */
    public function getHistoriqueCulturess(): Collection
    {
        if (!$this->historiqueCulturess instanceof Collection) {
            $this->historiqueCulturess = new ArrayCollection();
        }
        return $this->historiqueCulturess;
    }

    public function addHistoriqueCultures(HistoriqueCultures $historiqueCultures): static
    {
        if (!$this->getHistoriqueCulturess()->contains($historiqueCultures)) {
            $this->getHistoriqueCulturess()->add($historiqueCultures);
        }
        return $this;
    }

    public function removeHistoriqueCultures(HistoriqueCultures $historiqueCultures): static
    {
        $this->getHistoriqueCulturess()->removeElement($historiqueCultures);
        return $this;
    }

    public function getIdParcelle(): ?int
    {
        return $this->id_parcelle;
    }

    public function getNomParcelle(): ?string
    {
        return $this->nom_parcelle;
    }

    public function setNomParcelle(string $nom_parcelle): static
    {
        $this->nom_parcelle = $nom_parcelle;

        return $this;
    }

    public function getCoordonneesGps(): ?string
    {
        return $this->coordonnees_gps;
    }

    public function setCoordonneesGps(?string $coordonnees_gps): static
    {
        $this->coordonnees_gps = $coordonnees_gps;

        return $this;
    }

    public function getTypeSol(): ?string
    {
        return $this->type_sol;
    }

    public function setTypeSol(?string $type_sol): static
    {
        $this->type_sol = $type_sol;

        return $this;
    }

    public function addCulturess(Cultures $culturess): static
    {
        if (!$this->culturess->contains($culturess)) {
            $this->culturess->add($culturess);
            $culturess->setParcelles($this);
        }

        return $this;
    }

    public function removeCulturess(Cultures $culturess): static
    {
        if ($this->culturess->removeElement($culturess)) {
            // set the owning side to null (unless already changed)
            if ($culturess->getParcelles() === $this) {
                $culturess->setParcelles(null);
            }
        }

        return $this;
    }

    public function addHistoriqueCulturess(HistoriqueCultures $historiqueCulturess): static
    {
        if (!$this->historiqueCulturess->contains($historiqueCulturess)) {
            $this->historiqueCulturess->add($historiqueCulturess);
            $historiqueCulturess->setParcelles($this);
        }

        return $this;
    }

    public function removeHistoriqueCulturess(HistoriqueCultures $historiqueCulturess): static
    {
        if ($this->historiqueCulturess->removeElement($historiqueCulturess)) {
            // set the owning side to null (unless already changed)
            if ($historiqueCulturess->getParcelles() === $this) {
                $historiqueCulturess->setParcelles(null);
            }
        }

        return $this;
    }

}
