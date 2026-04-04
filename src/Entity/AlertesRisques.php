<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\AlertesRisquesRepository;

#[ORM\Entity(repositoryClass: AlertesRisquesRepository::class)]
#[ORM\Table(name: 'alertes_risques')]
class AlertesRisques
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_alerte = null;

    public function getId_alerte(): ?int
    {
        return $this->id_alerte;
    }

    public function setId_alerte(int $id_alerte): static
    {
        $this->id_alerte = $id_alerte;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_alerte = null;

    public function getType_alerte(): ?string
    {
        return $this->type_alerte;
    }

    public function setType_alerte(?string $type_alerte): static
    {
        $this->type_alerte = $type_alerte;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_alerte = null;

    public function getDate_alerte(): ?\DateTimeInterface
    {
        return $this->date_alerte;
    }

    public function setDate_alerte(\DateTimeInterface $date_alerte): static
    {
        $this->date_alerte = $date_alerte;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Cultures::class, inversedBy: 'alertesRisquess')]
    #[ORM\JoinColumn(name: 'id_culture', referencedColumnName: 'id_culture')]
    private ?Cultures $cultures = null;

    public function getCultures(): ?Cultures
    {
        return $this->cultures;
    }

    public function setCultures(?Cultures $cultures): static
    {
        $this->cultures = $cultures;
        return $this;
    }

    public function getIdAlerte(): ?int
    {
        return $this->id_alerte;
    }

    public function getTypeAlerte(): ?string
    {
        return $this->type_alerte;
    }

    public function setTypeAlerte(?string $type_alerte): static
    {
        $this->type_alerte = $type_alerte;

        return $this;
    }

    public function getDateAlerte(): ?\DateTime
    {
        return $this->date_alerte;
    }

    public function setDateAlerte(\DateTime $date_alerte): static
    {
        $this->date_alerte = $date_alerte;

        return $this;
    }

}
