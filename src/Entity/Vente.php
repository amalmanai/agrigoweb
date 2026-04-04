<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\VenteRepository;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
#[ORM\Table(name: 'vente')]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_vente = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    public function getId_vente(): ?int
    {
        return $this->id_vente;
    }

    public function setId_vente(int $id_vente): static
    {
        $this->id_vente = $id_vente;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_vente = null;

    public function getDate_vente(): ?\DateTimeInterface
    {
        return $this->date_vente;
    }

    public function setDate_vente(\DateTimeInterface $date_vente): static
    {
        $this->date_vente = $date_vente;
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

    public function getIdVente(): ?int
    {
        return $this->id_vente;
    }

    public function getDateVente(): ?\DateTime
    {
        return $this->date_vente;
    }

    public function setDateVente(\DateTime $date_vente): static
    {
        $this->date_vente = $date_vente;

        return $this;
    }

}
