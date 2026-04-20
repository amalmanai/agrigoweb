<?php

namespace App\Entity;

use App\Repository\RecolteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecolteRepository::class)]
#[ORM\Table(name: 'recolte')]
class Recolte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_recolte')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_produit', length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le nom doit faire au moins 2 caractères.", maxMessage: "Le nom ne peut pas dépasser 100 caractères.")]
    private ?string $name = null;

    #[ORM\Column(name: 'quantite', nullable: true)]
    #[Assert\NotBlank(message: "La quantité est obligatoire.")]
    #[Assert\Positive(message: "La quantité doit être supérieure à zéro.")]
    private ?float $quantity = null;

    #[ORM\Column(name: 'unite', length: 20, nullable: true)]
    #[Assert\NotBlank(message: "L'unité est obligatoire.")]
    private ?string $unit = null;

    #[ORM\Column(name: 'date_recolte', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "La date de récolte est obligatoire.")]
    private ?\DateTimeInterface $harvestDate = null;

    #[ORM\Column(name: 'cout_production', nullable: true)]
    #[Assert\NotBlank(message: "Le coût de production est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le coût doit être positif ou nul.")]
    private ?float $productionCost = null;

    #[ORM\Column(name: 'id_user', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'adresse', length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\ManyToOne(targetEntity: Parcelle::class)]
    #[ORM\JoinColumn(name: 'parcelle_id', referencedColumnName: 'id_parcelle', nullable: true, onDelete: 'SET NULL')]
    private ?Parcelle $parcelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getHarvestDate(): ?\DateTimeInterface
    {
        return $this->harvestDate;
    }

    public function setHarvestDate(?\DateTimeInterface $harvestDate): static
    {
        $this->harvestDate = $harvestDate;
        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getProductionCost(): ?float
    {
        return $this->productionCost;
    }

    public function setProductionCost(?float $productionCost): static
    {
        $this->productionCost = $productionCost;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Recolte #'.$this->id;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getParcelle(): ?Parcelle
    {
        return $this->parcelle;
    }

    public function setParcelle(?Parcelle $parcelle): static
    {
        $this->parcelle = $parcelle;

        return $this;
    }
}
