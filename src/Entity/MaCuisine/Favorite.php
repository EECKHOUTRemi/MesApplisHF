<?php

namespace App\Entity\MaCuisine;

use App\Entity\User;
use App\Repository\MaCuisine\FavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recette mise en favori par un utilisateur.
 *
 * La contrainte d'unicité (utilisateur, recette) garantit qu'une même recette n'est
 * mise en favori qu'une fois par personne : la bascule du bouton favori s'appuie
 * dessus. Les deux clés étrangères sont en `ON DELETE CASCADE`, le favori n'ayant
 * aucun sens sans son utilisateur ni sans sa recette.
 */
#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_favorite_user_recipe', columns: ['user_id', 'recipe_id'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favorites')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Recipe $recipe;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @param User $user
     * @param Recipe $recipe
     */
    public function __construct(User $user, Recipe $recipe)
    {
        $this->user = $user;
        $this->recipe = $recipe;
        $this->createdAt = new \DateTimeImmutable();
    }

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /** @return User */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /** @return Recipe */
    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    /**
     * @param Recipe $recipe
     * @return self
     */
    public function setRecipe(Recipe $recipe): self
    {
        $this->recipe = $recipe;
        return $this;
    }

    /** @return \DateTimeImmutable */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeImmutable $createdAt
     * @return self
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
