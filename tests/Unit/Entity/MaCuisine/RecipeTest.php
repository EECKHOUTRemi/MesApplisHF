<?php

namespace App\Tests\Unit\Entity\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use PHPUnit\Framework\TestCase;

/**
 * Teste les accesseurs de l'entité Recipe, en particulier les métadonnées
 * ajoutées à la fiche (portions, temps, difficulté, budget) et la description
 * désormais obligatoire.
 */
class RecipeTest extends TestCase
{
    public function testMetaAccessorsAreNullByDefault(): void
    {
        $recipe = new Recipe();

        $this->assertNull($recipe->getPortions());
        $this->assertNull($recipe->getTime());
        $this->assertNull($recipe->getDifficulty());
        $this->assertNull($recipe->getCost());
    }

    public function testMetaAccessors(): void
    {
        $recipe = new Recipe();

        $recipe->setPortions(4)
            ->setTime(45)
            ->setDifficulty(2)
            ->setCost(3);

        $this->assertSame(4, $recipe->getPortions());
        $this->assertSame(45, $recipe->getTime());
        $this->assertSame(2, $recipe->getDifficulty());
        $this->assertSame(3, $recipe->getCost());
    }

    public function testMetaAccessorsAcceptNullAgain(): void
    {
        $recipe = (new Recipe())
            ->setPortions(4)
            ->setTime(45)
            ->setDifficulty(2)
            ->setCost(3);

        $recipe->setPortions(null)
            ->setTime(null)
            ->setDifficulty(null)
            ->setCost(null);

        $this->assertNull($recipe->getPortions());
        $this->assertNull($recipe->getTime());
        $this->assertNull($recipe->getDifficulty());
        $this->assertNull($recipe->getCost());
    }

    public function testDescriptionIsAString(): void
    {
        $recipe = new Recipe();

        $recipe->setDescription('Une description obligatoire.');

        $this->assertSame('Une description obligatoire.', $recipe->getDescription());
    }
}
