<?php

namespace App\Entity;

use App\Repository\VisiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: VisiteRepository::class)]
#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: "unique_creneau",
            columns: ["logement_id", "date_visite", "heure_visite"]
        )
    ]
)]
class Visite
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_ACCEPTEE = 'ACCEPTEE';
    public const STATUT_REFUSEE = 'REFUSEE';
    // 🔹 futur si propriétaire propose modification
    public const STATUT_MODIF_PROPOSEE = 'MODIF_PROPOSEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 📅 Date
    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "La date de visite est obligatoire")]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\GreaterThanOrEqual("today", message: "La date doit être aujourd’hui ou future")]
    private ?\DateTimeInterface $dateVisite = null;

    // ⏰ Heure
    #[ORM\Column(type: 'time')]
    #[Assert\NotBlank(message: "L'heure de visite est obligatoire")]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $heureVisite = null;

    // 📝 Note
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Regex(
        pattern: "/^[^<>]*$/",
        message: "Caractères interdits dans la note"
    )]
    private ?string $note = null;

    // 📊 Statut
    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: [
            self::STATUT_EN_ATTENTE,
            self::STATUT_ACCEPTEE,
            self::STATUT_REFUSEE,
            self::STATUT_MODIF_PROPOSEE
        ],
        message: "Statut invalide"
    )]
    private string $statut = self::STATUT_EN_ATTENTE;

    // 🕓 Date création
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // 👤 CLIENT
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'visitesClient')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    // 🏠 PROPRIETAIRE
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'visitesProprietaire')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $proprietaire = null;

    // 🏡 LOGEMENT
    #[ORM\ManyToOne(targetEntity: Logement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Logement $logement = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_EN_ATTENTE;
    }

    /* =========================================================
     * ================= VALIDATION MÉTIER =====================
     * ========================================================= */

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        if (!$this->dateVisite || !$this->heureVisite) {
            return;
        }

        // 🔹 Date + heure future
        $dateTimeVisite = new \DateTime(
            $this->dateVisite->format('Y-m-d') . ' ' .
            $this->heureVisite->format('H:i:s')
        );

        if ($dateTimeVisite <= new \DateTime()) {
            $context->buildViolation('La date et l’heure doivent être futures.')
                ->atPath('heureVisite')
                ->addViolation();
        }

        // 🔹 Client ne peut pas réserver son propre logement
        if (
            $this->client &&
            $this->proprietaire &&
            $this->client->getId() === $this->proprietaire->getId()
        ) {
            $context->buildViolation('Vous ne pouvez pas réserver votre propre logement.')
                ->atPath('dateVisite')
                ->addViolation();
        }
    }

    /* =========================================================
     * ================= GETTERS / SETTERS =====================
     * ========================================================= */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateVisite(): ?\DateTimeInterface
    {
        return $this->dateVisite;
    }

    public function setDateVisite(?\DateTimeInterface $dateVisite): self
    {
        $this->dateVisite = $dateVisite;
        return $this;
    }

    public function getHeureVisite(): ?\DateTimeInterface
    {
        return $this->heureVisite;
    }

    public function setHeureVisite(?\DateTimeInterface $heureVisite): self
    {
        $this->heureVisite = $heureVisite;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $allowed = [
            self::STATUT_EN_ATTENTE,
            self::STATUT_ACCEPTEE,
            self::STATUT_REFUSEE,
            self::STATUT_MODIF_PROPOSEE
        ];

        if (!in_array($statut, $allowed)) {
            throw new \InvalidArgumentException("Statut invalide");
        }

        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getProprietaire(): ?User
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?User $proprietaire): self
    {
        $this->proprietaire = $proprietaire;
        return $this;
    }

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): self
    {
        $this->logement = $logement;
        return $this;
    }
}