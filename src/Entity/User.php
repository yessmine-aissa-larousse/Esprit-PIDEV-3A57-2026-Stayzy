<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private string $email = '';

    /** @var array<string> */
    #[ORM\Column]
    private array $roles = [];

    // ✅ FIX Security : #[Ignore] empêche la sérialisation du mot de passe
    #[Ignore]
    #[ORM\Column]
    private string $password = '';

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 50)]
    private string $nom = '';

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 50)]
    private string $prenom = '';

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(min: 8, max: 20)]
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

    /** @var Collection<int, Logement> */
    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'proprietaire', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $logements;

    /** @var Collection<int, Logement> */
    #[ORM\ManyToMany(targetEntity: Logement::class, inversedBy: 'utilisateursFavoris')]
    #[ORM\JoinTable(name: 'user_favoris')]
    private Collection $favoris;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $approvalStatus = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $approvalDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'destinataire')]
    private Collection $notifications;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceDescriptor = null;

    // ✅ FIX Security : #[Ignore] empêche la sérialisation du token
    #[Ignore]
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $resetPasswordToken = null;

    // ✅ FIX Security : #[Ignore] empêche la sérialisation
    #[Ignore]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $resetPasswordExpiresAt = null;

    public function __construct()
    {
        $this->createdAt   = new \DateTimeImmutable();
        $this->roles       = ['ROLE_USER'];
        $this->isVerified  = false;
        $this->isActive    = true;
        $this->logements   = new ArrayCollection();
        $this->favoris     = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    /** @param array<string> $roles */
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    /** @return array<string> */
    public function getRoleNames(): array
    {
        return array_map(fn($r) => str_replace('ROLE_', '', $r), $this->getRoles());
    }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function getSalt(): ?string { return null; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getTel(): ?string { return $this->tel; }
    public function setTel(?string $tel): self { $this->tel = $tel; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }
    public function setLastLogin(?\DateTime $lastLogin): self { $this->lastLogin = $lastLogin; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): self { $this->isVerified = $isVerified; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): self { $this->profilePicture = $profilePicture; return $this; }

    /** @return Collection<int, Logement> */
    public function getLogements(): Collection { return $this->logements; }

    public function addLogement(Logement $logement): static
    {
        if (!$this->logements->contains($logement)) {
            $this->logements->add($logement);
            $logement->setProprietaire($this);
        }
        return $this;
    }

    public function removeLogement(Logement $logement): static
    {
        if ($this->logements->removeElement($logement)) {
            if ($logement->getProprietaire() === $this) {
                $logement->setProprietaire(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Logement> */
    public function getFavoris(): Collection { return $this->favoris; }

    public function addFavori(Logement $logement): static
    {
        if (!$this->favoris->contains($logement)) {
            $this->favoris->add($logement);
            $logement->addUtilisateurFavori($this);
        }
        return $this;
    }

    public function removeFavori(Logement $logement): static
    {
        if ($this->favoris->removeElement($logement)) {
            $logement->removeUtilisateurFavori($this);
        }
        return $this;
    }

    public function isFavori(Logement $logement): bool { return $this->favoris->contains($logement); }
    public function getNombreFavoris(): int { return $this->favoris->count(); }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection { return $this->notifications; }
    public function getNotificationsNonLues(): int { return $this->notifications->filter(fn($n) => !$n->isLu())->count(); }

    public function getApprovalStatus(): ?string { return $this->approvalStatus; }
    public function setApprovalStatus(?string $approvalStatus): self { $this->approvalStatus = $approvalStatus; return $this; }

    public function isPending(): bool  { return $this->approvalStatus === self::STATUS_PENDING; }
    public function isApproved(): bool { return $this->approvalStatus === self::STATUS_APPROVED; }
    public function isRejected(): bool { return $this->approvalStatus === self::STATUS_REJECTED; }

    public function getApprovalStatusLabel(): string
    {
        return match($this->approvalStatus) {
            self::STATUS_PENDING  => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            default               => 'N/A'
        };
    }

    public function getApprovalDate(): ?\DateTime { return $this->approvalDate; }
    public function setApprovalDate(?\DateTime $approvalDate): self { $this->approvalDate = $approvalDate; return $this; }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): self { $this->rejectionReason = $rejectionReason; return $this; }

    public function getFullName(): string { return $this->prenom . ' ' . $this->nom; }
    public function __toString(): string { return $this->getFullName(); }

    public function isClient(): bool      { return in_array('ROLE_CLIENT', $this->roles); }
    public function isProprietaire(): bool { return in_array('ROLE_PROPRIETAIRE', $this->roles); }

    public function canAccessAccount(): bool
    {
        if (!$this->isProprietaire()) return $this->isActive;
        return $this->isActive && $this->isApproved();
    }

    public function getTrustScore(): int
    {
        $score = 0;
        if ($this->isVerified)    $score += 20;
        if ($this->profilePicture) $score += 15;
        if ($this->tel)           $score += 15;
        if ($this->adresse)       $score += 10;
        if ($this->isActive)      $score += 10;
        if ($this->isProprietaire() && $this->approvalStatus === self::STATUS_APPROVED) $score += 30;
        if ($this->nom && $this->prenom && $this->email && $this->tel && $this->profilePicture) $score += 10;
        return min($score, 100);
    }

    public function getTrustBadgeLevel(): string
    {
        $score = $this->getTrustScore();
        if ($score >= 71) return 'gold';
        if ($score >= 41) return 'silver';
        return 'bronze';
    }

    public function getTrustBadgeName(): string
    {
        return match($this->getTrustBadgeLevel()) {
            'gold'   => 'Or',
            'silver' => 'Argent',
            default  => 'Bronze'
        };
    }

    /** @return array<array<string, mixed>> */
    public function getTrustScoreSuggestions(): array
    {
        $suggestions = [];
        if (!$this->isVerified)    $suggestions[] = ['icon' => 'bi-envelope-check', 'text' => 'Vérifiez votre email pour gagner +20 points', 'points' => 20, 'color' => 'warning'];
        if (!$this->profilePicture) $suggestions[] = ['icon' => 'bi-camera', 'text' => 'Ajoutez une photo de profil pour gagner +15 points', 'points' => 15, 'color' => 'info'];
        if (!$this->tel)           $suggestions[] = ['icon' => 'bi-phone', 'text' => 'Ajoutez votre numéro de téléphone pour gagner +15 points', 'points' => 15, 'color' => 'info'];
        if (!$this->adresse)       $suggestions[] = ['icon' => 'bi-geo-alt', 'text' => 'Ajoutez votre adresse pour gagner +10 points', 'points' => 10, 'color' => 'info'];
        if ($this->isProprietaire() && $this->approvalStatus === self::STATUS_PENDING)
            $suggestions[] = ['icon' => 'bi-hourglass-split', 'text' => "Votre compte propriétaire est en attente d'approbation (+30 points)", 'points' => 30, 'color' => 'warning'];
        return $suggestions;
    }

    public function getFaceDescriptor(): ?string { return $this->faceDescriptor; }
    public function setFaceDescriptor(?string $faceDescriptor): static { $this->faceDescriptor = $faceDescriptor; return $this; }

    public function getResetPasswordToken(): ?string { return $this->resetPasswordToken; }
    public function setResetPasswordToken(?string $token): static { $this->resetPasswordToken = $token; return $this; }

    public function getResetPasswordExpiresAt(): ?\DateTime { return $this->resetPasswordExpiresAt; }
    public function setResetPasswordExpiresAt(?\DateTime $date): static { $this->resetPasswordExpiresAt = $date; return $this; }
}