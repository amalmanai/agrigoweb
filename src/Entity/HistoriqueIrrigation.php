<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\HistoriqueIrrigationRepository;

#[ORM\Entity(repositoryClass: HistoriqueIrrigationRepository::class)]
#[ORM\Table(name: 'historique_irrigation')]
class HistoriqueIrrigation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: SystemeIrrigation::class, inversedBy: 'historiqueIrrigations')]
    #[ORM\JoinColumn(name: 'id_systeme', referencedColumnName: 'id_systeme')]
    private ?SystemeIrrigation $systemeIrrigation = null;

    public function getSystemeIrrigation(): ?SystemeIrrigation
    {
        return $this->systemeIrrigation;
    }

    public function setSystemeIrrigation(?SystemeIrrigation $systemeIrrigation): static
    {
        $this->systemeIrrigation = $systemeIrrigation;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_irrigation = null;

    public function getDate_irrigation(): ?\DateTimeInterface
    {
        return $this->date_irrigation;
    }

    public function setDate_irrigation(\DateTimeInterface $date_irrigation): static
    {
        $this->date_irrigation = $date_irrigation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $duree_minutes = null;

    public function getDuree_minutes(): ?int
    {
        return $this->duree_minutes;
    }

    public function setDuree_minutes(int $duree_minutes): static
    {
        $this->duree_minutes = $duree_minutes;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $volume_eau = null;

    public function getVolume_eau(): ?string
    {
        return $this->volume_eau;
    }

    public function setVolume_eau(?string $volume_eau): static
    {
        $this->volume_eau = $volume_eau;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $humidite_avant = null;

    public function getHumidite_avant(): ?string
    {
        return $this->humidite_avant;
    }

    public function setHumidite_avant(?string $humidite_avant): static
    {
        $this->humidite_avant = $humidite_avant;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_declenchement = null;

    public function getType_declenchement(): ?string
    {
        return $this->type_declenchement;
    }

    public function setType_declenchement(?string $type_declenchement): static
    {
        $this->type_declenchement = $type_declenchement;
        return $this;
    }

    public function getDateIrrigation(): ?\DateTime
    {
        return $this->date_irrigation;
    }

    public function setDateIrrigation(\DateTime $date_irrigation): static
    {
        $this->date_irrigation = $date_irrigation;

        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->duree_minutes;
    }

    public function setDureeMinutes(int $duree_minutes): static
    {
        $this->duree_minutes = $duree_minutes;

        return $this;
    }

    public function getVolumeEau(): ?string
    {
        return $this->volume_eau;
    }

    public function setVolumeEau(?string $volume_eau): static
    {
        $this->volume_eau = $volume_eau;

        return $this;
    }

    public function getHumiditeAvant(): ?string
    {
        return $this->humidite_avant;
    }

    public function setHumiditeAvant(?string $humidite_avant): static
    {
        $this->humidite_avant = $humidite_avant;

        return $this;
    }

    public function getTypeDeclenchement(): ?string
    {
        return $this->type_declenchement;
    }

    public function setTypeDeclenchement(?string $type_declenchement): static
    {
        $this->type_declenchement = $type_declenchement;

        return $this;
    }

}
