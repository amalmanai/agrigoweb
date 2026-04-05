<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\SystemeIrrigationRepository;

#[ORM\Entity(repositoryClass: SystemeIrrigationRepository::class)]
#[ORM\Table(name: 'systeme_irrigation')]
class SystemeIrrigation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_systeme = null;

    public function getId_systeme(): ?int
    {
        return $this->id_systeme;
    }

    public function setId_systeme(int $id_systeme): static
    {
        $this->id_systeme = $id_systeme;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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
    private ?string $nom_systeme = null;

    public function getNom_systeme(): ?string
    {
        return $this->nom_systeme;
    }

    public function setNom_systeme(string $nom_systeme): static
    {
        $this->nom_systeme = $nom_systeme;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $seuil_humidite = null;

    public function getSeuil_humidite(): ?string
    {
        return $this->seuil_humidite;
    }

    public function setSeuil_humidite(?string $seuil_humidite): static
    {
        $this->seuil_humidite = $seuil_humidite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $mode = null;

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(?string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_creation = null;

    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: HistoriqueIrrigation::class, mappedBy: 'systemeIrrigation')]
    private Collection $historiqueIrrigations;

    public function __construct()
    {
        $this->historiqueIrrigations = new ArrayCollection();
    }

    /**
     * @return Collection<int, HistoriqueIrrigation>
     */
    public function getHistoriqueIrrigations(): Collection
    {
        if (!$this->historiqueIrrigations instanceof Collection) {
            $this->historiqueIrrigations = new ArrayCollection();
        }
        return $this->historiqueIrrigations;
    }

    public function addHistoriqueIrrigation(HistoriqueIrrigation $historiqueIrrigation): static
    {
        if (!$this->getHistoriqueIrrigations()->contains($historiqueIrrigation)) {
            $this->getHistoriqueIrrigations()->add($historiqueIrrigation);
        }
        return $this;
    }

    public function removeHistoriqueIrrigation(HistoriqueIrrigation $historiqueIrrigation): static
    {
        $this->getHistoriqueIrrigations()->removeElement($historiqueIrrigation);
        return $this;
    }

    public function getIdSysteme(): ?int
    {
        return $this->id_systeme;
    }

    public function getIdParcelle(): ?int
    {
        return $this->id_parcelle;
    }

    public function setIdParcelle(int $id_parcelle): static
    {
        $this->id_parcelle = $id_parcelle;

        return $this;
    }

    public function getNomSysteme(): ?string
    {
        return $this->nom_systeme;
    }

    public function setNomSysteme(string $nom_systeme): static
    {
        $this->nom_systeme = $nom_systeme;

        return $this;
    }

    public function getSeuilHumidite(): ?string
    {
        return $this->seuil_humidite;
    }

    public function setSeuilHumidite(?string $seuil_humidite): static
    {
        $this->seuil_humidite = $seuil_humidite;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

}
