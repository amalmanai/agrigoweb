<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
#[ORM\Table(name: 'vente')]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_vente')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "La description doit faire au moins 3 caractères.", maxMessage: "La description ne peut pas dépasser 255 caractères.")]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Recolte::class)]
    #[ORM\JoinColumn(name: 'recolte_id', referencedColumnName: 'id_recolte', nullable: true, onDelete: 'SET NULL')]
    private ?Recolte $recolte = null;

    #[ORM\Column(name: 'prix', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être strictement positif.")]
    private ?string $price = null;

    #[ORM\Column(name: 'date_vente', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de vente est obligatoire.")]
    private ?\DateTimeInterface $saleDate = null;

    #[ORM\Column(name: 'buyer_name', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le nom de l'acheteur est obligatoire.")]
    private ?string $buyerName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private ?string $status = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: "L'évaluation doit être entre 1 et 5 étoiles.")]
    private ?int $rating = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $deliveryLocation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deliveryLatitude = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deliveryLongitude = null;

    #[ORM\Column(name: 'marketplace_listing', options: ['default' => true])]
    private bool $isMarketplaceListing = true;

    #[ORM\Column(name: 'available_quantity', nullable: true)]
    #[Assert\Positive(message: 'La quantite disponible doit etre strictement positive.')]
    private ?float $availableQuantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getRecolte(): ?Recolte
    {
        return $this->recolte;
    }

    public function setRecolte(?Recolte $recolte): static
    {
        $this->recolte = $recolte;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getSaleDate(): ?\DateTimeInterface
    {
        return $this->saleDate;
    }

    public function setSaleDate(?\DateTimeInterface $saleDate): static
    {
        $this->saleDate = $saleDate;
        return $this;
    }

    public function getBuyerName(): ?string
    {
        return $this->buyerName;
    }

    public function setBuyerName(?string $buyerName): static
    {
        $this->buyerName = $buyerName;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getDeliveryLocation(): ?string
    {
        return $this->deliveryLocation;
    }

    public function setDeliveryLocation(?string $deliveryLocation): static
    {
        $this->deliveryLocation = $deliveryLocation;
        return $this;
    }

    public function getDeliveryLatitude(): ?string
    {
        return $this->deliveryLatitude;
    }

    public function setDeliveryLatitude(?string $deliveryLatitude): static
    {
        $this->deliveryLatitude = $deliveryLatitude;
        return $this;
    }

    public function getDeliveryLongitude(): ?string
    {
        return $this->deliveryLongitude;
    }

    public function setDeliveryLongitude(?string $deliveryLongitude): static
    {
        $this->deliveryLongitude = $deliveryLongitude;
        return $this;
    }

    public function isMarketplaceListing(): bool
    {
        return $this->isMarketplaceListing;
    }

    public function setIsMarketplaceListing(bool $isMarketplaceListing): static
    {
        $this->isMarketplaceListing = $isMarketplaceListing;

        return $this;
    }

    public function getAvailableQuantity(): ?float
    {
        return $this->availableQuantity;
    }

    public function setAvailableQuantity(?float $availableQuantity): static
    {
        $this->availableQuantity = $availableQuantity;

        return $this;
    }
}
