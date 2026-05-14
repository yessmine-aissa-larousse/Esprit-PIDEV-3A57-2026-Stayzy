<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 5, max: 255)]
    private string $titre = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(nullable: true, type: Types::JSON)]
    private ?array $adresse = null;

    // ✅ FIX : ?float → float (non-nullable)
    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif")]
    private float $prix = 0;

    // ✅ FIX : ?int → int (non-nullable)
    #[ORM\Column]
    #[Assert\NotBlank(message: "La superficie est obligatoire")]
    #[Assert\Positive(message: "La superficie doit être un nombre positif")]
    private int $superficie = 0;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de chambres est obligatoire")]
    #[Assert\Positive(message: "Le nombre de chambres doit être un nombre positif")]
    private int $nombreChambres = 0;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de salles de bain est obligatoire")]
    #[Assert\Positive(message: "Le nombre de salles de bain doit être un nombre positif")]
    private int $nombreSalleDeBain = 0;

    /** @var array<string>|null */
    #[ORM\Column(nullable: true)]
    private ?array $amenites = null;

    // ✅ FIX : ?bool → bool (non-nullable)
    #[ORM\Column]
    private bool $disponible = true;

    /** @var array<string>|null */
    #[ORM\Column(nullable: true)]
    private ?array $photos = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPrincipale = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteMoyenne = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalAvis = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire")]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'logements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $proprietaire = null;

    // FIX : ?\DateTimeImmutable → \DateTimeImmutable (non-nullable)
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoris')]
    private Collection $utilisateursFavoris;

    /** @var Collection<int, Promotion> */
    // FIX : cascade persist ajouté
    #[ORM\OneToMany(targetEntity: Promotion::class, mappedBy: 'logement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $promotions;

    public function __construct()
    {
        $this->createdAt            = new \DateTimeImmutable();
        $this->disponible           = true;
        $this->noteMoyenne          = null;
        $this->totalAvis            = 0;
        $this->utilisateursFavoris  = new ArrayCollection();
        $this->promotions           = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    /** @return array<string, mixed>|null */
    public function getAdresse(): ?array { return $this->adresse; }
    /** @param array<string, mixed>|null $adresse */
    public function setAdresse(?array $adresse): static { $this->adresse = $adresse; return $this; }

    public function getPrix(): float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getSuperficie(): int { return $this->superficie; }
    public function setSuperficie(int $superficie): static { $this->superficie = $superficie; return $this; }

    public function getNombreChambres(): int { return $this->nombreChambres; }
    public function setNombreChambres(int $nombreChambres): static { $this->nombreChambres = $nombreChambres; return $this; }

    public function getNombreSalleDeBain(): int { return $this->nombreSalleDeBain; }
    public function setNombreSalleDeBain(int $nombreSalleDeBain): static { $this->nombreSalleDeBain = $nombreSalleDeBain; return $this; }

    /** @return array<string>|null */
    public function getAmenites(): ?array { return $this->amenites; }
    /** @param array<string>|null $amenites */
    public function setAmenites(?array $amenites): static { $this->amenites = $amenites; return $this; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    /** @return array<string>|null */
    public function getPhotos(): ?array { return $this->photos; }
    /** @param array<string>|null $photos */
    public function setPhotos(?array $photos): static { $this->photos = $photos; return $this; }

    public function getPhotoPrincipale(): ?string { return $this->photoPrincipale; }
    public function setPhotoPrincipale(?string $photoPrincipale): static { $this->photoPrincipale = $photoPrincipale; return $this; }

    public function getNoteMoyenne(): ?float { return $this->noteMoyenne; }
    public function setNoteMoyenne(?float $noteMoyenne): static { $this->noteMoyenne = $noteMoyenne; return $this; }

    public function getTotalAvis(): ?int { return $this->totalAvis; }
    public function setTotalAvis(?int $totalAvis): static { $this->totalAvis = $totalAvis; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }

    public function getProprietaire(): ?User { return $this->proprietaire; }
    public function setProprietaire(?User $proprietaire): static { $this->proprietaire = $proprietaire; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    /** @return Collection<int, User> */
    public function getUtilisateursFavoris(): Collection { return $this->utilisateursFavoris; }

    public function addUtilisateurFavori(User $user): static
    {
        if (!$this->utilisateursFavoris->contains($user)) {
            $this->utilisateursFavoris->add($user);
        }
        return $this;
    }

    public function removeUtilisateurFavori(User $user): static
    {
        $this->utilisateursFavoris->removeElement($user);
        return $this;
    }

    public function getNombreFavoris(): int { return $this->utilisateursFavoris->count(); }
    public function estEnFavoriPour(User $user): bool { return $this->utilisateursFavoris->contains($user); }

    /** @return Collection<int, Promotion> */
    public function getPromotions(): Collection { return $this->promotions; }

    public function addPromotion(Promotion $promotion): static
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
            $promotion->setLogement($this);
        }
        return $this;
    }

    public function removePromotion(Promotion $promotion): static
    {
        if ($this->promotions->removeElement($promotion)) {
            if ($promotion->getLogement() === $this) {
                $promotion->setLogement(null);
            }
        }
        return $this;
    }

    public function getPromoActive(): ?\App\Entity\Promotion
    {
        $now = new \DateTime();
        foreach ($this->promotions as $promo) {
            if ($promo->isActive() && $promo->getDateDebut() && $promo->getDateFin()) {
                $debut = clone $promo->getDateDebut(); $debut->setTime(0, 0, 0);
                $fin   = clone $promo->getDateFin();   $fin->setTime(23, 59, 59);
                if ($debut <= $now && $now <= $fin) {
                    return $promo;
                }
            }
        }
        return null;
    }

    public function getPrixFinal(): float
    {
        $promo = $this->getPromoActive();
        return $promo !== null ? $promo->getPrixPromo() : $this->prix;
    }
}