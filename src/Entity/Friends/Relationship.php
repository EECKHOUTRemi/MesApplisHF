<?php

namespace App\Entity\Friends;

use App\Entity\User;
use App\Repository\Friends\RelationshipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Relation sociale entre deux utilisateurs (amitié, demande, blocage, etc.).
 */
#[ORM\Entity(repositoryClass: RelationshipRepository::class)]
class Relationship
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete:"CASCADE")]
    private ?User $user1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete:"CASCADE")]
    private ?User $user2 = null;

    #[ORM\Column]
    #[Assert\Choice(choices: self::STATUSES)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return User|null */
    public function getuser1(): ?User
    {
        return $this->user1;
    }

    /**
     * @param User|null $user1
     * @return static
     */
    public function setuser1(?User $user1): static
    {
        $this->user1 = $user1;

        return $this;
    }

    /** @return User|null */
    public function getuser2(): ?User
    {
        return $this->user2;
    }

    /**
     * @param User|null $user2
     * @return static
     */
    public function setuser2(?User $user2): static
    {
        $this->user2 = $user2;

        return $this;
    }

    /** @return string|null */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return \DateTimeImmutable|null */
    public function getCreatedAt(): ?\DateTimeImmutable
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
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeImmutable|null $updatedAt
     * @return static
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
