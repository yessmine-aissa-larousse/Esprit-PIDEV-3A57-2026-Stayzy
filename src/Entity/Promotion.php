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

    // DB: logement_id nullable
    #[ORM\ManyToOne(targetEntity: Logement::class, inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Logement $logement = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le titre de la promotion est obligatoire')]
    #[Assert\Length(max: 100)]
    private ?string $titre = null;

    // DB: int nullable default 0
    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'Le pourcentage est obligatoire')]
    #[Assert\Range(min: 1, max: 99, notInRangeMessage: 'Le pourcentage doit être entre 1% et 99%')]
    private ?int $pourcentage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'La date de fin doit être après la date de début')]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9]+$/',
        message: 'Le code promo doit contenir uniquement des lettres majuscules et chiffres',
        match: true
    )]
    private ?string $codePromo = null;

    // DB: tinyint nullable default 1
    #[ORM\Column(nullable: true, options: ['default' => 1])]
    private ?bool $active = true;

    // DB: datetime nullable default CURRENT_TIMESTAMP
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): static { $this->logement = $logement; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getPourcentage(): ?int { return $this->pourcentage; }
    public function setPourcentage(int $pourcentage): static { $this->pourcentage = $pourcentage; return $this; }

    public function getDateDebut(): ?\DateTime { return $this->dateDebut; }
    public function setDateDebut(?\DateTime $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTime { return $this->dateFin; }
    public function setDateFin(?\DateTime $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getCodePromo(): ?string { return $this->codePromo; }
    public function setCodePromo(?string $codePromo): static
    {
        $this->codePromo = $codePromo ? strtoupper($codePromo) : null;
        return $this;
    }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(?bool $active): static { $this->active = $active; return $this; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }

    // ══════════════════════════════════════════════
    // STATUT
    // ══════════════════════════════════════════════

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
        $now   = new \DateTime();
        $debut = clone $this->dateDebut; $debut->setTime(0, 0, 0);
        return $debut > $now;
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

    // ══════════════════════════════════════════════
    // CALCULS PRIX
    // ══════════════════════════════════════════════

    public function getPrixPromo(): float
    {
        $prix = $this->logement?->getPrix() ?? 0;
        return round($prix * (1 - ($this->pourcentage ?? 0) / 100), 2);
    }

    public function getEconomie(): float
    {
        $prix = $this->logement?->getPrix() ?? 0;
        return round($prix * ($this->pourcentage ?? 0) / 100, 2);
    }
}