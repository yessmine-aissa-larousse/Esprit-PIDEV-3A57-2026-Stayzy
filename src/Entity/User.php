<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Le nom doit contenir au moins 2 caractères")]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Le prénom doit contenir au moins 2 caractères")]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(min: 8, max: 20, minMessage: "Le téléphone doit contenir au moins 8 caractères")]
    #[Assert\Regex(pattern: "/^[0-9+\-\s\(\)]{8,20}$/", message: "Numéro de téléphone invalide")]
    private ?string $tel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide")]
    private ?string $imageUrl = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastLogin = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    // Statut d'approbation pour les propriétaires
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $approvalStatus = null;

    // Date d'approbation/rejet
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $approvalDate = null;

    // Raison du rejet (optionnel)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->isVerified = false;
        $this->isActive = true;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ========== ID ==========
    public function getId(): ?int { return $this->id; }

    // ========== EMAIL ==========
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // ========== ROLES ==========
    public function getRoles(): array
    {
        $roles = $this->roles;
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getRoleNames(): array
    {
        return array_map(function($role) {
            return str_replace('ROLE_', '', $role);
        }, $this->getRoles());
    }

    // ========== PASSWORD ==========
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}
    public function getSalt(): ?string { return null; }

    // ========== NOM ==========
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    // ========== PRENOM ==========
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    // ========== TELEPHONE ==========
    public function getTel(): ?string { return $this->tel; }
    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    // ========== ADRESSE ==========
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    // ========== IMAGE URL ==========
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    // ========== IS ACTIVE ==========
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    // ========== CREATED AT ==========
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // ========== LAST LOGIN ==========
    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }
    public function setLastLogin(?\DateTime $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    // ========== IS VERIFIED ==========
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // ========== APPROVAL STATUS ==========
    public function getApprovalStatus(): ?string { return $this->approvalStatus; }
    public function setApprovalStatus(?string $approvalStatus): self
    {
        $this->approvalStatus = $approvalStatus;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->approvalStatus === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approvalStatus === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approvalStatus === self::STATUS_REJECTED;
    }

    public function getApprovalStatusLabel(): string
    {
        return match($this->approvalStatus) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            default => 'N/A'
        };
    }

    // ========== APPROVAL DATE ==========
    public function getApprovalDate(): ?\DateTime { return $this->approvalDate; }
    public function setApprovalDate(?\DateTime $approvalDate): self
    {
        $this->approvalDate = $approvalDate;
        return $this;
    }

    // ========== REJECTION REASON ==========
    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    // ========== HELPER METHODS ==========
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    // Vérifier si l'utilisateur est un propriétaire
    public function isProprietaire(): bool
    {
        return in_array('ROLE_PROPRIETAIRE', $this->roles);
    }

    // Vérifier si le propriétaire peut accéder à son compte
    public function canAccessAccount(): bool
    {
        // Les clients et admins peuvent toujours accéder
        if (!$this->isProprietaire()) {
            return $this->isActive;
        }
        // Les propriétaires doivent être approuvés et actifs
        return $this->isActive && $this->isApproved();
    }
}