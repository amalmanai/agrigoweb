<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\CulturesRepository;

#[ORM\Entity(repositoryClass: CulturesRepository::class)]
#[ORM\Table(name: 'cultures')]
class Cultures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_culture = null;

    public function getId_culture(): ?int
    {
        return $this->id_culture;
    }

    public function setId_culture(int $id_culture): static
    {
        $this->id_culture = $id_culture;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_culture = null;

    public function getNom_culture(): ?string
    {
        return $this->nom_culture;
    }

    public function setNom_culture(string $nom_culture): static
    {
        $this->nom_culture = $nom_culture;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_semis = null;

    public function getDate_semis(): ?\DateTimeInterface
    {
        return $this->date_semis;
    }

    public function setDate_semis(\DateTimeInterface $date_semis): static
    {
        $this->date_semis = $date_semis;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $etat_croissance = null;

    public function getEtat_croissance(): ?string
    {
        return $this->etat_croissance;
    }

    public function setEtat_croissance(?string $etat_croissance): static
    {
        $this->etat_croissance = $etat_croissance;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $rendement_prevu = null;

    public function getRendement_prevu(): ?float
    {
        return $this->rendement_prevu;
    }

    public function setRendement_prevu(?float $rendement_prevu): static
    {
        $this->rendement_prevu = $rendement_prevu;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Parcelles::class, inversedBy: 'culturess')]
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

    #[ORM\OneToMany(targetEntity: AlertesRisques::class, mappedBy: 'cultures')]
    private Collection $alertesRisquess;

    public function __construct()
    {
        $this->alertesRisquess = new ArrayCollection();
    }

    /**
     * @return Collection<int, AlertesRisques>
     */
    public function getAlertesRisquess(): Collection
    {
        if (!$this->alertesRisquess instanceof Collection) {
            $this->alertesRisquess = new ArrayCollection();
        }
        return $this->alertesRisquess;
    }

    public function addAlertesRisques(AlertesRisques $alertesRisques): static
    {
        if (!$this->getAlertesRisquess()->contains($alertesRisques)) {
            $this->getAlertesRisquess()->add($alertesRisques);
        }
        return $this;
    }

    public function removeAlertesRisques(AlertesRisques $alertesRisques): static
    {
        $this->getAlertesRisquess()->removeElement($alertesRisques);
        return $this;
    }

    public function getIdCulture(): ?int
    {
        return $this->id_culture;
    }

    public function getNomCulture(): ?string
    {
        return $this->nom_culture;
    }

    public function setNomCulture(string $nom_culture): static
    {
        $this->nom_culture = $nom_culture;

        return $this;
    }

    public function getDateSemis(): ?\DateTime
    {
        return $this->date_semis;
    }

    public function setDateSemis(\DateTime $date_semis): static
    {
        $this->date_semis = $date_semis;

        return $this;
    }

    public function getEtatCroissance(): ?string
    {
        return $this->etat_croissance;
    }

    public function setEtatCroissance(?string $etat_croissance): static
    {
        $this->etat_croissance = $etat_croissance;

        return $this;
    }

    public function getRendementPrevu(): ?string
    {
        return $this->rendement_prevu;
    }

    public function setRendementPrevu(?string $rendement_prevu): static
    {
        $this->rendement_prevu = $rendement_prevu;

        return $this;
    }

    public function addAlertesRisquess(AlertesRisques $alertesRisquess): static
    {
        if (!$this->alertesRisquess->contains($alertesRisquess)) {
            $this->alertesRisquess->add($alertesRisquess);
            $alertesRisquess->setCultures($this);
        }

        return $this;
    }

    public function removeAlertesRisquess(AlertesRisques $alertesRisquess): static
    {
        if ($this->alertesRisquess->removeElement($alertesRisquess)) {
            // set the owning side to null (unless already changed)
            if ($alertesRisquess->getCultures() === $this) {
                $alertesRisquess->setCultures(null);
            }
        }

        return $this;
    }

}
