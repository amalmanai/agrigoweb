<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlerteRisqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AlerteRisqueRepository::class)]
#[ORM\Table(name: 'alertes_risques')]
class AlerteRisque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_alerte', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'type_alerte', type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le type d\'alerte est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le type alerte ne doit pas depasser {{ limit }} caracteres.')]
    private ?string $typeAlerte = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(min: 5, minMessage: 'La description doit contenir au moins {{ limit }} caracteres.')]
    private ?string $description = null;

    #[ORM\Column(name: 'date_alerte', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Assert\NotNull(message: 'La date de l\'alerte est obligatoire.')]
    #[Assert\LessThanOrEqual('now', message: 'La date de l\'alerte ne peut pas etre dans le futur.')]
    private ?\DateTimeImmutable $dateAlerte = null;

    #[ORM\ManyToOne(targetEntity: Culture::class, inversedBy: 'alertesRisques')]
    #[ORM\JoinColumn(name: 'id_culture', referencedColumnName: 'id_culture', nullable: true)]
    #[Assert\NotNull(message: 'La culture associee est obligatoire.')]
    private ?Culture $culture = null;

    public function __construct()
    {
        $this->dateAlerte = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeAlerte(): ?string
    {
        return $this->typeAlerte;
    }

    public function setTypeAlerte(?string $typeAlerte): self
    {
        $this->typeAlerte = $typeAlerte;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateAlerte(): ?\DateTimeImmutable
    {
        return $this->dateAlerte;
    }

    public function setDateAlerte(?\DateTimeImmutable $dateAlerte): self
    {
        $this->dateAlerte = $dateAlerte;

        return $this;
    }

    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    public function setCulture(?Culture $culture): self
    {
        $this->culture = $culture;

        return $this;
    }
}
