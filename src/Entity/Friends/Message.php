<?php

namespace App\Entity\Friends;

use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Repository\Friends\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Message échangé au sein d'une conversation entre amis.
 */
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private Conversation $conversation;

    /** Null lorsque le compte de l'auteur a été supprimé : le fil reste lisible. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column]
    private \DateTimeImmutable $sentAt;

    /** Date de lecture par le destinataire ; null tant que le message n'est pas lu. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Recipe $recipeAttached = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return Conversation */
    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    /**
     * @param Conversation $conversation
     * @return static
     */
    public function setConversation(Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    /** @return null|User */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @param null|User $author
     * @return static
     */
    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /** @return \DateTimeImmutable */
    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTimeImmutable $sentAt
     * @return static
     */
    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /** @return \DateTimeImmutable|null */
    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    /**
     * @param \DateTimeImmutable|null $readAt
     * @return static
     */
    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

        return $this;
    }

    /** @return bool */
    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    /** @return string|null */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /** @return Recipe|null */
    public function getRecipeAttached(): ?Recipe
    {
        return $this->recipeAttached;
    }

    /**
     * @param Recipe|null $recipeAttached
     * @return static
     */
    public function setRecipeAttached(?Recipe $recipeAttached): static
    {
        $this->recipeAttached = $recipeAttached;

        return $this;
    }
}
