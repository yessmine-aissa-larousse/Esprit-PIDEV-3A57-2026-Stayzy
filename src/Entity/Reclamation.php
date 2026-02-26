<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Reponse;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    //  Sujet obligatoire + min longueur
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le sujet est obligatoire.")]
    #[Assert\Length(
        min: 5,
        minMessage: "Le sujet doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $sujet = null;

    //  Description obligatoire + min longueur
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $description = null;

    //  Date automatique
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateReclamation = null;

    //  Statut par défaut
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $statut = null;

    //  Relation obligatoire avec User
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reclamations')]
#[ORM\JoinColumn(nullable: false)]
private ?User $user = null;

    //  Relation avec Reponse (OneToMany)
    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponse::class, orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->dateReclamation = new \DateTimeImmutable();
        $this->statut = "EN_ATTENTE";
        $this->reponses = new ArrayCollection();
    }

    #[ORM\Column(nullable: true)]
private ?int $riskScore = 0;

#[ORM\Column(nullable: true)]
private ?bool $isAbusive = false;

    // Getters & Setters

    public function getRiskScore(): ?int
{
    return $this->riskScore;
}

public function setRiskScore(?int $riskScore): self
{
    $this->riskScore = $riskScore;
    return $this;
}


public function isAbusive(): ?bool
{
    return $this->isAbusive;
}

public function setIsAbusive(?bool $isAbusive): self
{
    $this->isAbusive = $isAbusive;
    return $this;
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): self
    {
        $this->sujet = $sujet;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateReclamation(): ?\DateTimeImmutable
    {
        return $this->dateReclamation;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // =========================
    // Relation Reponses
    // =========================

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

   public function addReponse(Reponse $reponse): self
{
    if (!$this->reponses->contains($reponse)) {
        $this->reponses->add($reponse);
        $reponse->setReclamation($this);

        //   statut automatiquement
        $this->statut = "TRAITEE";
    }

    return $this;
}

public function removeReponse(Reponse $reponse): self
{
    if ($this->reponses->removeElement($reponse)) {
        if ($reponse->getReclamation() === $this) {
            $reponse->setReclamation(null);
        }

        //  réponses
        if ($this->reponses->isEmpty()) {
            $this->statut = "EN_ATTENTE";
        }
    }

    return $this;
}

}
