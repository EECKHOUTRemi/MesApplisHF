<?php

namespace App\Entity\MaCuisine;

use App\Repository\MaCuisine\IngredientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ingrédient du catalogue MaCuisine, référencé dans les recettes via RefRecipeIngredient.
 */
#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

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
}
