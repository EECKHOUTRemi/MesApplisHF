<?php

namespace App\Entity\Friends;

use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Repository\Friends\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;
    
    #[ORM\Column]
    private \DateTimeImmutable $sentAt;
    
    #[ORM\Column(nullable: true)]
    private \DateTimeImmutable $readAt;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Recipe $recipeAttached = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileAttached = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getReadAt(): \DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

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

    public function getRecipeAttached(): ?Recipe
    {
        return $this->recipeAttached;
    }

    public function setRecipeAttached(?Recipe $recipeAttached): static
    {
        $this->recipeAttached = $recipeAttached;

        return $this;
    }

    public function getFileAttached(): ?string
    {
        return $this->fileAttached;
    }

    public function setFileAttached(?string $fileAttached): static
    {
        $this->fileAttached = $fileAttached;

        return $this;
    }
}
