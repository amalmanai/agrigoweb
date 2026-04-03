<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HistoriqueCultureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HistoriqueCultureRepository::class)]
#[ORM\Table(name: 'historique_cultures')]
class HistoriqueCulture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_historique', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Parcelle::class, inversedBy: 'historiqueCultures')]
    #[ORM\JoinColumn(name: 'id_parcelle', referencedColumnName: 'id_parcelle', nullable: true, onDelete: 'SET NULL')]
    private ?Parcelle $parcelle = null;

    #[ORM\Column(name: 'ancienne_culture', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'L\'ancienne culture ne doit pas depasser {{ limit }} caracteres.')]
    private ?string $ancienneCulture = null;

    #[ORM\Column(name: 'date_recolte_effective', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateRecolteEffective = null;

    #[ORM\Column(name: 'rendement_final', type: 'float', nullable: true)]
    #[Assert\Type(type: 'float', message: 'Le rendement final doit etre numerique.')]
    #[Assert\Range(min: 0, max: 1000000, notInRangeMessage: 'Le rendement final doit etre compris entre {{ min }} et {{ max }}.')]
    private ?float $rendementFinal = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAncienneCulture(): ?string
    {
        return $this->ancienneCulture;
    }

    public function setAncienneCulture(?string $ancienneCulture): self
    {
        $this->ancienneCulture = $ancienneCulture;

        return $this;
    }

    public function getDateRecolteEffective(): ?\DateTimeInterface
    {
        return $this->dateRecolteEffective;
    }

    public function setDateRecolteEffective(?\DateTimeInterface $dateRecolteEffective): self
    {
        $this->dateRecolteEffective = $dateRecolteEffective;

        return $this;
    }

    public function getRendementFinal(): ?float
    {
        return $this->rendementFinal;
    }

    public function setRendementFinal(?float $rendementFinal): self
    {
        $this->rendementFinal = $rendementFinal;

        return $this;
    }
}
