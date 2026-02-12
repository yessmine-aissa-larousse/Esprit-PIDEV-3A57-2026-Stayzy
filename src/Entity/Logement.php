<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresse = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column]
    private ?int $superficie = null;

    #[ORM\Column]
    private ?int $nombreChambres = null;

    #[ORM\Column]
    private ?int $nombreSalleDeBain = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $amenites = null;

    #[ORM\Column]
    private ?bool $disponible = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $photos = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPrincipale = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteMoyenne = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalAvis = null;

    // 🔗 RELATION AVEC RESERVATION
    #[ORM\OneToMany(mappedBy: 'logement', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    // ======= GETTERS & SETTERS =======
    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getAdresse(): ?array { return $this->adresse; }
    public function setAdresse(?array $adresse): static { $this->adresse = $adresse; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getSuperficie(): ?int { return $this->superficie; }
    public function setSuperficie(int $superficie): static { $this->superficie = $superficie; return $this; }

    public function getNombreChambres(): ?int { return $this->nombreChambres; }
    public function setNombreChambres(int $nombreChambres): static { $this->nombreChambres = $nombreChambres; return $this; }

    public function getNombreSalleDeBain(): ?int { return $this->nombreSalleDeBain; }
    public function setNombreSalleDeBain(int $nombreSalleDeBain): static { $this->nombreSalleDeBain = $nombreSalleDeBain; return $this; }

    public function getAmenites(): ?array { return $this->amenites; }
    public function setAmenites(?array $amenites): static { $this->amenites = $amenites; return $this; }

    public function isDisponible(): ?bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    public function getPhotos(): ?array { return $this->photos; }
    public function setPhotos(?array $photos): static { $this->photos = $photos; return $this; }

    public function getPhotoPrincipale(): ?string { return $this->photoPrincipale; }
    public function setPhotoPrincipale(?string $photoPrincipale): static { $this->photoPrincipale = $photoPrincipale; return $this; }

    public function getNoteMoyenne(): ?float { return $this->noteMoyenne; }
    public function setNoteMoyenne(?float $noteMoyenne): static { $this->noteMoyenne = $noteMoyenne; return $this; }

    public function getTotalAvis(): ?int { return $this->totalAvis; }
    public function setTotalAvis(?int $totalAvis): static { $this->totalAvis = $totalAvis; return $this; }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection { return $this->reservations; }
}
