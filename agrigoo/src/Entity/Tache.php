<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\TacheRepository;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
#[ORM\Table(name: 'tache')]
class Tache
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $tittre_tache = null;

    public function getTittre_tache(): ?string
    {
        return $this->tittre_tache;
    }

    public function setTittre_tache(string $tittre_tache): static
    {
        $this->tittre_tache = $tittre_tache;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description_tache = null;

    public function getDescription_tache(): ?string
    {
        return $this->description_tache;
    }

    public function setDescription_tache(string $description_tache): static
    {
        $this->description_tache = $description_tache;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_tache = null;

    public function getType_tache(): ?string
    {
        return $this->type_tache;
    }

    public function setType_tache(string $type_tache): static
    {
        $this->type_tache = $type_tache;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_user = null;

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(int $id_user): static
    {
        $this->id_user = $id_user;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_tache = null;

    public function getDate_tache(): ?\DateTimeInterface
    {
        return $this->date_tache;
    }

    public function setDate_tache(\DateTimeInterface $date_tache): static
    {
        $this->date_tache = $date_tache;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    private ?string $heure_debut_tache = null;

    public function getHeure_debut_tache(): ?string
    {
        return $this->heure_debut_tache;
    }

    public function setHeure_debut_tache(string $heure_debut_tache): static
    {
        $this->heure_debut_tache = $heure_debut_tache;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    private ?string $heure_fin_tache = null;

    public function getHeure_fin_tache(): ?string
    {
        return $this->heure_fin_tache;
    }

    public function setHeure_fin_tache(string $heure_fin_tache): static
    {
        $this->heure_fin_tache = $heure_fin_tache;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status_tache = null;

    public function getStatus_tache(): ?string
    {
        return $this->status_tache;
    }

    public function setStatus_tache(string $status_tache): static
    {
        $this->status_tache = $status_tache;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $remarque_tache = null;

    public function getRemarque_tache(): ?string
    {
        return $this->remarque_tache;
    }

    public function setRemarque_tache(string $remarque_tache): static
    {
        $this->remarque_tache = $remarque_tache;
        return $this;
    }

    public function getTittreTache(): ?string
    {
        return $this->tittre_tache;
    }

    public function setTittreTache(string $tittre_tache): static
    {
        $this->tittre_tache = $tittre_tache;

        return $this;
    }

    public function getDescriptionTache(): ?string
    {
        return $this->description_tache;
    }

    public function setDescriptionTache(string $description_tache): static
    {
        $this->description_tache = $description_tache;

        return $this;
    }

    public function getTypeTache(): ?string
    {
        return $this->type_tache;
    }

    public function setTypeTache(string $type_tache): static
    {
        $this->type_tache = $type_tache;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

    public function getDateTache(): ?\DateTime
    {
        return $this->date_tache;
    }

    public function setDateTache(\DateTime $date_tache): static
    {
        $this->date_tache = $date_tache;

        return $this;
    }

    public function getHeureDebutTache(): ?\DateTime
    {
        return $this->heure_debut_tache;
    }

    public function setHeureDebutTache(\DateTime $heure_debut_tache): static
    {
        $this->heure_debut_tache = $heure_debut_tache;

        return $this;
    }

    public function getHeureFinTache(): ?\DateTime
    {
        return $this->heure_fin_tache;
    }

    public function setHeureFinTache(\DateTime $heure_fin_tache): static
    {
        $this->heure_fin_tache = $heure_fin_tache;

        return $this;
    }

    public function getStatusTache(): ?string
    {
        return $this->status_tache;
    }

    public function setStatusTache(string $status_tache): static
    {
        $this->status_tache = $status_tache;

        return $this;
    }

    public function getRemarqueTache(): ?string
    {
        return $this->remarque_tache;
    }

    public function setRemarqueTache(string $remarque_tache): static
    {
        $this->remarque_tache = $remarque_tache;

        return $this;
    }

}
