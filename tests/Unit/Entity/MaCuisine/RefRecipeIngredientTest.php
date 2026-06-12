<?php

namespace App\Tests\Unit\Entity\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\MaCuisine\RefRecipeIngredient;
use PHPUnit\Framework\TestCase;

class RefRecipeIngredientTest extends TestCase
{
    public function testAccessors(): void
    {
        $ref = new RefRecipeIngredient();
        $recipe = new Recipe();
        $ingredient = new Ingredient();

        $ref->setRecipe($recipe)
            ->setIngredient($ingredient)
            ->setQuantity(250.0)
            ->setUnite('g');

        $this->assertSame($recipe, $ref->getRecipe());
        $this->assertSame($ingredient, $ref->getIngredient());
        $this->assertSame(250.0, $ref->getQuantity());
        $this->assertSame('g', $ref->getUnite());
    }
}
