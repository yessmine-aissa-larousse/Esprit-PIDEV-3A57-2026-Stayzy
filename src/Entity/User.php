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
#[ORM\Table(name: 'user')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
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

    // ⭐ NOUVEAU : Collection des logements du propriétaire
    /**
     * @var Collection<int, Logement>
     */
    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'proprietaire', orphanRemoval: true)]
    private Collection $logements;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->isVerified = false;
        $this->isActive = true;
        $this->logements = new ArrayCollection(); // ⭐ NOUVEAU
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ========== Tous vos getters/setters existants (inchangés) ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getSalt(): ?string
    {
        return null;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
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

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // ========== NOUVEAUX : Gestion des logements ==========

    /**
     * @return Collection<int, Logement>
     */
    public function getLogements(): Collection
    {
        return $this->logements;
    }

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

    // ========== Helper Methods (inchangés) ==========

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}