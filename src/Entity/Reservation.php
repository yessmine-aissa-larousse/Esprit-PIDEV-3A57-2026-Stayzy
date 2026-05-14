<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ FIX : ?\DateTime → \DateTime (non-nullable)
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    private \DateTime $dateDebut;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    private \DateTime $dateFin;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $nombrePersonnes = null;

    // ✅ FIX : float pour prix → decimal stocké en string
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $prixTotal = null;

    // ✅ FIX : ?string → string (non-nullable)
    #[ORM\Column(length: 255)]
    private string $status = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $messageDemande = null;

    /** @var Collection<int, Commande> */
    // ✅ FIX : orphanRemoval=true ajouté
    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Commande::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $commandes;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Logement $logement = null;

    public function __construct()
    {
        $this->commandes  = new ArrayCollection();
        $this->dateDebut  = new \DateTime();
        $this->dateFin    = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getDateDebut(): \DateTime { return $this->dateDebut; }
    public function setDateDebut(\DateTime $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): \DateTime { return $this->dateFin; }
    public function setDateFin(\DateTime $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getNombrePersonnes(): ?int { return $this->nombrePersonnes; }
    public function setNombrePersonnes(int $nombrePersonnes): static { $this->nombrePersonnes = $nombrePersonnes; return $this; }

    // ✅ getter retourne float pour compatibilité
    public function getPrixTotal(): ?float { return $this->prixTotal !== null ? (float) $this->prixTotal : null; }
    public function setPrixTotal(float $prixTotal): static { $this->prixTotal = (string) $prixTotal; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getMessageDemande(): ?string { return $this->messageDemande; }
    public function setMessageDemande(?string $messageDemande): static { $this->messageDemande = $messageDemande; return $this; }

    /** @return Collection<int, Commande> */
    public function getCommandes(): Collection { return $this->commandes; }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setReservation($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getReservation() === $this) {
                $commande->setReservation(null);
            }
        }
        return $this;
    }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): static { $this->logement = $logement; return $this; }
}