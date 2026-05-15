<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comment')]
#[ORM\Index(columns: ['created_at'], name: 'idx_comment_created_at')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du commentaire est obligatoire')]
    #[Assert\Length(min: 3, max: 2000, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères', maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères')]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'L\'auteur ne peut pas dépasser {{ limit }} caractères')]
    private ?string $author = null;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le chemin de l\'image ne peut pas dépasser {{ limit }} caractères')]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Un commentaire doit être lié à un post')]
    private ?Post $post = null;

    #[ORM\Column(name: 'avis_count', options: ['default' => 0])]
    private int $avisCount = 0;

    #[ORM\Column(name: 'dislike_count', options: ['default' => 0])]
    private int $dislikeCount = 0;

    // DB: tinyint(1) NOT NULL (no default)
    #[ORM\Column(options: ['default' => 0])]
    private bool $signaled = false;

    // DB: blob nullable
    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $reactions = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Comment $parent = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $replies;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
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

    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    public function setParent(?Comment $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(Comment $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }

    public function removeReply(Comment $reply): static
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }
        return $this;
    }

    public function isSignaled(): bool { return $this->signaled; }
    public function setSignaled(bool $signaled): static { $this->signaled = $signaled; return $this; }

    public function getReactions(): mixed { return $this->reactions; }
    public function setReactions(mixed $reactions): static { $this->reactions = $reactions; return $this; }
}
