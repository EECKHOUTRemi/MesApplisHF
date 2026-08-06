<?php

namespace App\Entity\MaCuisine;

use App\Entity\MaCuisine\RefRecipeIngredient;
use App\Entity\User;
use App\Repository\MaCuisine\RecipeRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recette MaCuisine : nom, description, catégorie, ingrédients (via RefRecipeIngredient) et ustensiles.
 */
#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 30)]
    #[Assert\Length(max: 30, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\Column(length: 600, nullable: true)]
    #[Assert\Length(max: 600, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    /**
     * @var Collection<int, RefRecipeIngredient>
     */
    #[ORM\OneToMany(
        mappedBy: 'recipe',
        targetEntity: RefRecipeIngredient::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?Collection $refRecipeIngredients = null;

    /**
     * @var Collection<int, Utensil>
     */
    #[ORM\ManyToMany(targetEntity: Utensil::class)]
    private ?Collection $utensil = null;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->refRecipeIngredients = new ArrayCollection();
        $this->utensil = new ArrayCollection();
    }

    /**
     * @return Collection<int, RefRecipeIngredient>
     */
    public function getRefRecipeIngredients(): Collection
    {
        return $this->refRecipeIngredients;
    }

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return User|null */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @param User|null $author
     * @return static
     */
    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    /** @return string|null */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Utensil>
     */
    public function getUtensil(): Collection
    {
        return $this->utensil;
    }

    /**
     * @param Utensil $utensil
     * @return static
     */
    public function addUtensil(Utensil $utensil): static
    {
        if (!$this->utensil->contains($utensil)) {
            $this->utensil->add($utensil);
        }

        return $this;
    }

    /**
     * @param Utensil $utensil
     * @return static
     */
    public function removeUtensil(Utensil $utensil): static
    {
        $this->utensil->removeElement($utensil);

        return $this;
    }

    /** @return Category|null */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return static
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

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
}
