<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'proprietaire', orphanRemoval: true)]
    private Collection $logements;

    #[ORM\ManyToMany(targetEntity: Logement::class, inversedBy: 'utilisateursFavoris')]
    #[ORM\JoinTable(name: 'user_favoris')]
    private Collection $favoris;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'destinataire')]
    private Collection $notifications;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $approvalStatus = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $approvalDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
    private Collection $commandes;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
    private Collection $reservations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->isVerified = false;
        $this->isActive = true;
        $this->logements = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ========== GETTERS / SETTERS ==========

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function getSalt(): ?string { return null; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getTel(): ?string { return $this->tel; }
    public function setTel(?string $tel): self { $this->tel = $tel; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): self { $this->profilePicture = $profilePicture; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }
    public function setLastLogin(?\DateTime $lastLogin): self { $this->lastLogin = $lastLogin; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): self { $this->isVerified = $isVerified; return $this; }

    // ======= Relations Logement / Favoris / Notifications =======

    public function getLogements(): Collection { return $this->logements; }
    public function addLogement(Logement $logement): static {
        if (!$this->logements->contains($logement)) {
            $this->logements->add($logement);
            $logement->setProprietaire($this);
        }
        return $this;
    }
    public function removeLogement(Logement $logement): static {
        if ($this->logements->removeElement($logement) && $logement->getProprietaire() === $this) {
            $logement->setProprietaire(null);
        }
        return $this;
    }

    public function getFavoris(): Collection { return $this->favoris; }
    public function addFavori(Logement $logement): static {
        if (!$this->favoris->contains($logement)) {
            $this->favoris->add($logement);
            $logement->addUtilisateurFavori($this);
        }
        return $this;
    }
    public function removeFavori(Logement $logement): static {
        if ($this->favoris->removeElement($logement)) {
            $logement->removeUtilisateurFavori($this);
        }
        return $this;
    }

    public function getNotifications(): Collection { return $this->notifications; }
    public function getNotificationsNonLues(): int { return $this->notifications->filter(fn($n) => !$n->isLu())->count(); }

    // ======= Relations Commande / Reservation =======

    public function getCommandes(): Collection { return $this->commandes; }
    public function addCommande(Commande $commande): static {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setUser($this);
        }
        return $this;
    }
    public function removeCommande(Commande $commande): static {
        if ($this->commandes->removeElement($commande) && $commande->getUser() === $this) {
            $commande->setUser(null);
        }
        return $this;
    }

    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(Reservation $reservation): static {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this);
        }
        return $this;
    }
    public function removeReservation(Reservation $reservation): static {
        if ($this->reservations->removeElement($reservation) && $reservation->getUser() === $this) {
            $reservation->setUser(null);
        }
        return $this;
    }

    // ======= Approval & TrustScore =======

    public function getApprovalStatus(): ?string { return $this->approvalStatus; }
    public function setApprovalStatus(?string $approvalStatus): self { $this->approvalStatus = $approvalStatus; return $this; }

    public function getApprovalDate(): ?\DateTime { return $this->approvalDate; }
    public function setApprovalDate(?\DateTime $approvalDate): self { $this->approvalDate = $approvalDate; return $this; }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): self { $this->rejectionReason = $rejectionReason; return $this; }

    public function isPending(): bool { return $this->approvalStatus === self::STATUS_PENDING; }
    public function isApproved(): bool { return $this->approvalStatus === self::STATUS_APPROVED; }
    public function isRejected(): bool { return $this->approvalStatus === self::STATUS_REJECTED; }

    public function getApprovalStatusLabel(): string
    {
        return match($this->approvalStatus) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            default => 'N/A'
        };
    }

    // ======= Trust Score =======
    public function getTrustScore(): int
    {
        $score = 0;
        if ($this->isVerified) $score += 20;
        if ($this->profilePicture) $score += 15;
        if ($this->tel) $score += 15;
        if ($this->adresse) $score += 10;
        if ($this->isActive) $score += 10;
        if ($this->isProprietaire() && $this->approvalStatus === self::STATUS_APPROVED) $score += 30;
        if ($this->nom && $this->prenom && $this->email && $this->tel && $this->profilePicture) $score += 10;
        return min($score, 100);
    }

    public function getTrustBadgeLevel(): string
    {
        $score = $this->getTrustScore();
        return $score >= 71 ? 'gold' : ($score >= 41 ? 'silver' : 'bronze');
    }

    public function getTrustBadgeName(): string
    {
        return match($this->getTrustBadgeLevel()) {
            'gold' => 'Or',
            'silver' => 'Argent',
            'bronze' => 'Bronze',
            default => 'Bronze'
        };
    }

    public function getTrustScoreSuggestions(): array
    {
        $s = [];
        if (!$this->isVerified) $s[] = ['icon'=>'bi-envelope-check','text'=>"Vérifiez votre email",'points'=>20,'color'=>'warning'];
        if (!$this->profilePicture) $s[] = ['icon'=>'bi-camera','text'=>"Ajoutez une photo de profil",'points'=>15,'color'=>'info'];
        if (!$this->tel) $s[] = ['icon'=>'bi-phone','text'=>"Ajoutez votre numéro de téléphone",'points'=>15,'color'=>'info'];
        if (!$this->adresse) $s[] = ['icon'=>'bi-geo-alt','text'=>"Ajoutez votre adresse",'points'=>10,'color'=>'info'];
        if ($this->isProprietaire() && $this->approvalStatus === self::STATUS_PENDING) $s[] = ['icon'=>'bi-hourglass-split','text'=>"Compte en attente d'approbation",'points'=>30,'color'=>'warning'];
        return $s;
    }

    // ======= Helpers =======

    public function getFullName(): string { return $this->prenom . ' ' . $this->nom; }
    public function __toString(): string { return $this->getFullName(); }

    public function isClient(): bool { return in_array('ROLE_CLIENT', $this->roles); }
    public function isProprietaire(): bool { return in_array('ROLE_PROPRIETAIRE', $this->roles); }
    public function canAccessAccount(): bool {
        if (!$this->isProprietaire()) return $this->isActive;
        return $this->isActive && $this->isApproved();
    }
    // src/Entity/User.php

public function getNombreFavoris(): int
{
    return $this->favoris->count();
}

}