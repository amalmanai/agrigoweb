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
    private int $id;

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

    #[ORM\Column(name: 'cout_production', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Assert\NotBlank(message: "Le coût de production est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le coût doit être positif ou nul.")]
    private ?string $productionCost = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return isset($this->id) ? $this->id : null;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getProductionCost(): ?string
    {
        return $this->productionCost;
    }

    public function setProductionCost(float|int|string|null $productionCost): static
    {
        if ($productionCost === null || $productionCost === '') {
            $this->productionCost = null;

            return $this;
        }

        $this->productionCost = number_format((float) $productionCost, 2, '.', '');
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getIdUser();
    }

    public function setUserId(?int $userId): static
    {
        if ($userId === null) {
            $this->user = null;
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Recolte #'.$this->id;
    }
}
