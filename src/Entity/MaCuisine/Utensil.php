<?php

namespace App\Entity\MaCuisine;

use App\Entity\User;
use App\Repository\MaCuisine\UtensilRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ustensile de cuisine utilisé dans les recettes MaCuisine.
 */
#[ORM\Entity(repositoryClass: UtensilRepository::class)]
class Utensil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return string|null */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

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

    /** @return User|null */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     * @return static
     */
    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

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
