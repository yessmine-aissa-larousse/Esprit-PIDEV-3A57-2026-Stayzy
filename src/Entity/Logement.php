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

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true, type: Types::JSON)]
    private $adresse = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif")]
    private ?float $prix = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La superficie est obligatoire")]
    #[Assert\Positive(message: "La superficie doit être un nombre positif")]
    private ?int $superficie = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de chambres est obligatoire")]
    #[Assert\Positive(message: "Le nombre de chambres doit être un nombre positif")]
    private ?int $nombreChambres = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de salles de bain est obligatoire")]
    #[Assert\Positive(message: "Le nombre de salles de bain doit être un nombre positif")]
    private ?int $nombreSalleDeBain = null;

    #[ORM\Column(nullable: true, type: Types::JSON)]
    #[Assert\DisableAutoMapping]
    private $amenites = null;

    #[ORM\Column]
    private ?bool $disponible = null;

    #[ORM\Column(nullable: true, type: Types::JSON)]
    #[Assert\DisableAutoMapping]
    private $photos = null;

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

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoris')]
    private Collection $utilisateursFavoris;

    #[ORM\OneToMany(targetEntity: Promotion::class, mappedBy: 'logement', orphanRemoval: true)]
    private Collection $promotions;

    #[ORM\OneToMany(mappedBy: 'logement', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->disponible = true;
        $this->noteMoyenne = null;
        $this->totalAvis = 0;
        $this->utilisateursFavoris = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    // ====== GETTERS / SETTERS ======

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getAdresse(): ?array
    {
        if ($this->adresse === null) return null;
        if (is_array($this->adresse)) return $this->adresse;
        // Java stores adresse as a plain string — normalize to array so templates work
        $str = trim((string) $this->adresse, '"');
        return ['rue' => $str, 'ville' => $str, 'pays' => '', 'codePostal' => ''];
    }
    public function setAdresse(mixed $adresse): static { $this->adresse = $adresse; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getSuperficie(): ?int { return $this->superficie; }
    public function setSuperficie(int $superficie): static { $this->superficie = $superficie; return $this; }

    public function getNombreChambres(): ?int { return $this->nombreChambres; }
    public function setNombreChambres(int $nombreChambres): static { $this->nombreChambres = $nombreChambres; return $this; }

    public function getNombreSalleDeBain(): ?int { return $this->nombreSalleDeBain; }
    public function setNombreSalleDeBain(int $nombreSalleDeBain): static { $this->nombreSalleDeBain = $nombreSalleDeBain; return $this; }

    public function getAmenites(): ?array
    {
        if ($this->amenites === null) return null;
        if (is_array($this->amenites)) return $this->amenites;
        $str = trim((string) $this->amenites, '"');
        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }
    public function setAmenites(mixed $amenites): static { $this->amenites = $amenites; return $this; }

    public function isDisponible(): ?bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    public function getPhotos(): ?array
    {
        if ($this->photos === null) return null;
        if (is_array($this->photos)) return $this->photos;
        $str = trim((string) $this->photos, '"');
        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }
    public function setPhotos(mixed $photos): static { $this->photos = $photos; return $this; }

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

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    // ⭐ Favoris
    public function getUtilisateursFavoris(): Collection { return $this->utilisateursFavoris; }
    public function addUtilisateurFavori(User $user): static { if (!$this->utilisateursFavoris->contains($user)) { $this->utilisateursFavoris->add($user); } return $this; }
    public function removeUtilisateurFavori(User $user): static { $this->utilisateursFavoris->removeElement($user); return $this; }
    public function estEnFavoriPour(User $user): bool { return $this->utilisateursFavoris->contains($user); }

    // ⭐ Promotions
    public function getPromotions(): Collection { return $this->promotions; }
    public function addPromotion(Promotion $promotion): static {

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
            if ($promo->isActive()
                && $promo->getDateDebut() !== null
                && $promo->getDateFin() !== null) {
                $debut = clone $promo->getDateDebut(); $debut->setTime(0, 0, 0);
                $fin   = clone $promo->getDateFin();   $fin->setTime(23, 59, 59);
                if ($debut <= $now && $now <= $fin) {
                    return $promo;
                }
            }
        }
        return null;
    }


    // ⭐ Réservations
    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(Reservation $reservation): static {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setLogement($this);
        }
        return $this;
    }
    public function removeReservation(Reservation $reservation): static {
        if ($this->reservations->removeElement($reservation) && $reservation->getLogement() === $this) {
            $reservation->setLogement(null);
        }
        return $this;}
        /**
     * Retourne le prix final (avec promo si en cours, sinon prix normal)
     * Ta camarade appelle juste logement.getPrixFinal() ou logement.prixFinal en Twig
     */
    public function getPrixFinal(): float
    {
        $promo = $this->getPromoActive();
        if ($promo !== null) {
            return $promo->getPrixPromo();
        }
        return (float) $this->prix;

    }
    public function getNombreFavoris(): int
{
    return $this->utilisateursFavoris->count();
}
}