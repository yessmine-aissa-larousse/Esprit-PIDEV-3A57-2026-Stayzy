<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ FIX : ?string → string (non-nullable, correspond à la BDD)
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire")]
    #[Assert\Length(min: 3, max: 100)]
    private string $nom = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description de la catégorie est obligatoire")]
    #[Assert\Length(min: 10, max: 100)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icone = null;

    /** @var Collection<int, Logement> */
    // ✅ FIX : cascade persist ajouté (orphanRemoval=true sans cascade persist causait un warning)
    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'categorie', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $logements;

    public function __construct()
    {
        $this->logements = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getIcone(): ?string { return $this->icone; }
    public function setIcone(?string $icone): static { $this->icone = $icone; return $this; }

    /** @return Collection<int, Logement> */
    public function getLogements(): Collection { return $this->logements; }

    public function addLogement(Logement $logement): static
    {
        if (!$this->logements->contains($logement)) {
            $this->logements->add($logement);
            $logement->setCategorie($this);
        }
        return $this;
    }

    public function removeLogement(Logement $logement): static
    {
        if ($this->logements->removeElement($logement)) {
            if ($logement->getCategorie() === $this) {
                $logement->setCategorie(null);
            }
        }
        return $this;
    }
}