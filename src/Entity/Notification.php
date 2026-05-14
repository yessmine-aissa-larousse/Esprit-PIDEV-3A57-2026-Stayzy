<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 255)]
    private string $message = '';

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 50)]
    private string $type = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $destinataire = null;

    #[ORM\ManyToOne(targetEntity: Logement::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Logement $logement = null;

    #[ORM\Column(type: 'boolean')]
    private bool $lu = false;

    // ✅ FIX : ?\DateTimeImmutable → \DateTimeImmutable (non-nullable)
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lu = false;
    }

    public function getId(): ?int { return $this->id; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDestinataire(): ?User { return $this->destinataire; }
    public function setDestinataire(?User $destinataire): static { $this->destinataire = $destinataire; return $this; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): static { $this->logement = $logement; return $this; }

    public function isLu(): bool { return $this->lu; }
    public function setLu(bool $lu): static { $this->lu = $lu; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}