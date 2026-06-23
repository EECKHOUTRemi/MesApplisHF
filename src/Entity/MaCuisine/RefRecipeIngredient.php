<?php

namespace App\Entity\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Repository\MaCuisine\RefRecipeIngredientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de jointure Recipe ↔ Ingredient avec quantité et unité de mesure.
 * Clé primaire composite (recipe + ingredient).
 */
#[ORM\Entity(repositoryClass: RefRecipeIngredientRepository::class)]
class RefRecipeIngredient
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $recipe = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ingredient $ingredient = null;

    #[ORM\Column]
    private ?float $quantity = null;

    #[ORM\Column(length: 10)]
    private ?string $unite = null;

    /** @return Recipe|null */
    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    /**
     * @param Recipe|null $recipe
     * @return static
     */
    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    /** @return Ingredient|null */
    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    /**
     * @param Ingredient|null $ingredient
     * @return static
     */
    public function setIngredient(?Ingredient $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    /** @return float|null */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return static
     */
    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /** @return string|null */
    public function getUnite(): ?string
    {
        return $this->unite;
    }

    /**
     * @param string $unite
     * @return static
     */
    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }
}
