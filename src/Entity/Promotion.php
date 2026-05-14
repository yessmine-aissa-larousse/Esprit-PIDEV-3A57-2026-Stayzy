<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Logement::class, inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Logement $logement = null;

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le titre de la promotion est obligatoire')]
    #[Assert\Length(max: 100)]
    private string $titre = '';

    // ✅ FIX : ?int → int (non-nullable)
    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le pourcentage est obligatoire')]
    #[Assert\Range(min: 1, max: 99)]
    private int $pourcentage = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $codePromo = null;

    #[ORM\Column]
    private bool $active = true;

    // ✅ FIX : ?\DateTime → \DateTime (non-nullable, initialisé dans PrePersist)
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): static { $this->logement = $logement; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getPourcentage(): int { return $this->pourcentage; }
    public function setPourcentage(int $pourcentage): static { $this->pourcentage = $pourcentage; return $this; }

    public function getDateDebut(): ?\DateTime { return $this->dateDebut; }
    public function setDateDebut(?\DateTime $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTime { return $this->dateFin; }
    public function setDateFin(?\DateTime $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getCodePromo(): ?string { return $this->codePromo; }
    public function setCodePromo(?string $codePromo): static { $this->codePromo = $codePromo ? strtoupper($codePromo) : null; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    public function isEnCours(): bool
    {
        if (!$this->active || !$this->dateDebut || !$this->dateFin) return false;
        $now   = new \DateTime();
        $debut = clone $this->dateDebut; $debut->setTime(0, 0, 0);
        $fin   = clone $this->dateFin;   $fin->setTime(23, 59, 59);
        return $debut <= $now && $now <= $fin;
    }

    public function isFuture(): bool
    {
        if (!$this->active || !$this->dateDebut) return false;
        $debut = clone $this->dateDebut; $debut->setTime(0, 0, 0);
        return $debut > new \DateTime();
    }

    public function isExpiree(): bool
    {
        if (!$this->dateFin) return false;
        $fin = clone $this->dateFin; $fin->setTime(23, 59, 59);
        return $fin < new \DateTime();
    }

    public function getStatutLabel(): string
    {
        if ($this->isEnCours()) return 'active';
        if ($this->isFuture())  return 'future';
        if ($this->isExpiree()) return 'expiree';
        return 'inactive';
    }

    public function getPrixPromo(): float
    {
        $prix = $this->logement?->getPrix() ?? 0;
        return round($prix * (1 - $this->pourcentage / 100), 2);
    }

    public function getEconomie(): float
    {
        $prix = $this->logement?->getPrix() ?? 0;
        return round($prix * $this->pourcentage / 100, 2);
    }
}