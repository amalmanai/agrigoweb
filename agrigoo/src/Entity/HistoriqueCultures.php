<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\HistoriqueCulturesRepository;

#[ORM\Entity(repositoryClass: HistoriqueCulturesRepository::class)]
#[ORM\Table(name: 'historique_cultures')]
class HistoriqueCultures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_historique = null;

    public function getId_historique(): ?int
    {
        return $this->id_historique;
    }

    public function setId_historique(int $id_historique): static
    {
        $this->id_historique = $id_historique;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Parcelles::class, inversedBy: 'historiqueCulturess')]
    #[ORM\JoinColumn(name: 'id_parcelle', referencedColumnName: 'id_parcelle')]
    private ?Parcelles $parcelles = null;

    public function getParcelles(): ?Parcelles
    {
        return $this->parcelles;
    }

    public function setParcelles(?Parcelles $parcelles): static
    {
        $this->parcelles = $parcelles;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $ancienne_culture = null;

    public function getAncienne_culture(): ?string
    {
        return $this->ancienne_culture;
    }

    public function setAncienne_culture(?string $ancienne_culture): static
    {
        $this->ancienne_culture = $ancienne_culture;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_recolte_effective = null;

    public function getDate_recolte_effective(): ?\DateTimeInterface
    {
        return $this->date_recolte_effective;
    }

    public function setDate_recolte_effective(?\DateTimeInterface $date_recolte_effective): static
    {
        $this->date_recolte_effective = $date_recolte_effective;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $rendement_final = null;

    public function getRendement_final(): ?float
    {
        return $this->rendement_final;
    }

    public function setRendement_final(?float $rendement_final): static
    {
        $this->rendement_final = $rendement_final;
        return $this;
    }

    public function getIdHistorique(): ?int
    {
        return $this->id_historique;
    }

    public function getAncienneCulture(): ?string
    {
        return $this->ancienne_culture;
    }

    public function setAncienneCulture(?string $ancienne_culture): static
    {
        $this->ancienne_culture = $ancienne_culture;

        return $this;
    }

    public function getDateRecolteEffective(): ?\DateTime
    {
        return $this->date_recolte_effective;
    }

    public function setDateRecolteEffective(?\DateTime $date_recolte_effective): static
    {
        $this->date_recolte_effective = $date_recolte_effective;

        return $this;
    }

    public function getRendementFinal(): ?string
    {
        return $this->rendement_final;
    }

    public function setRendementFinal(?string $rendement_final): static
    {
        $this->rendement_final = $rendement_final;

        return $this;
    }

}
