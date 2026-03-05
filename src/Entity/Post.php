<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['created_at'], name: 'idx_post_created_at')]
#[ORM\Index(columns: ['is_published'], name: 'idx_post_published')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères')]
    private ?string $content = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'L\'extrait ne peut pas dépasser {{ limit }} caractères')]
    private ?string $excerpt = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'auteur est obligatoire')]
    #[Assert\Length(max: 180, maxMessage: 'L\'auteur ne peut pas dépasser {{ limit }} caractères')]
    private ?string $author = null;

    #[ORM\Column(name: 'is_published', options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le chemin de l\'image ne peut pas dépasser {{ limit }} caractères')]
    private ?string $image = null;

    #[ORM\Column(name: 'avis_count', options: ['default' => 0])]
    private int $avisCount = 0;

    #[ORM\Column(name: 'dislike_count', options: ['default' => 0])]
    private int $dislikeCount = 0;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    public function getCommentsCount(): int
    {
        return $this->comments->count();
    }

    public function getAvisCount(): int
    {
        return $this->avisCount;
    }

    public function setAvisCount(int $avisCount): static
    {
        $this->avisCount = $avisCount;
        return $this;
    }

    public function incrementAvis(): static
    {
        $this->avisCount++;
        return $this;
    }

    public function getDislikeCount(): int
    {
        return $this->dislikeCount;
    }

    public function setDislikeCount(int $dislikeCount): static
    {
        $this->dislikeCount = $dislikeCount;
        return $this;
    }

    public function incrementDislike(): static
    {
        $this->dislikeCount++;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
