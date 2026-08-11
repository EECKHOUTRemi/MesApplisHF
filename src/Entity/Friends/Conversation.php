<?php

namespace App\Entity\Friends;

use App\Entity\User;
use App\Repository\Friends\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fil de discussion entre deux utilisateurs amis.
 */
#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'conversations')]
    private Collection $users;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Date du dernier message ; dénormalisée pour trier la liste sans jointure. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->users     = new ArrayCollection();
        $this->messages  = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param User $user
     * @return static
     */
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    /**
     * @param User $user
     * @return static
     */
    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Retourne l'autre participant de la conversation, ou null s'il est introuvable.
     *
     * @param User $user
     * @return User|null
     */
    public function getOtherParticipant(User $user): ?User
    {
        $other = $this->users->filter(static fn (User $participant) => $participant !== $user)->first();

        return $other instanceof User ? $other : null;
    }

    /** @return \DateTimeImmutable */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeImmutable $createdAt
     * @return static
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return \DateTimeImmutable|null */
    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    /**
     * @param \DateTimeImmutable|null $lastMessageAt
     * @return static
     */
    public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @param Message $message
     * @return static
     */
    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    /**
     * @param Message $message
     * @return static
     */
    public function removeMessage(Message $message): static
    {
        $this->messages->removeElement($message);

        return $this;
    }
}
