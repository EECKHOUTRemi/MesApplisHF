<?php

namespace App\Entity\MaCuisine;

use App\Entity\User;
use App\Repository\MaCuisine\FavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recette mise en favori par un utilisateur.
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

    public function __construct(User $user, Recipe $recipe)
    {
        $this->user = $user;
        $this->recipe = $recipe;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
