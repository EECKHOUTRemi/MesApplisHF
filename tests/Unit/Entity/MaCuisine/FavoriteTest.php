<?php

namespace App\Tests\Unit\Entity\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Teste l'entité Favorite : construction (utilisateur, recette, horodatage) et accesseurs. */
class FavoriteTest extends TestCase
{
    public function testConstructorSetsUserRecipeAndCreatedAt(): void
    {
        $user = new User();
        $recipe = new Recipe();

        $before = new \DateTimeImmutable();
        $favorite = new Favorite($user, $recipe);
        $after = new \DateTimeImmutable();

        $this->assertNull($favorite->getId());
        $this->assertSame($user, $favorite->getUser());
        $this->assertSame($recipe, $favorite->getRecipe());
        $this->assertGreaterThanOrEqual($before, $favorite->getCreatedAt());
        $this->assertLessThanOrEqual($after, $favorite->getCreatedAt());
    }

    public function testAccessors(): void
    {
        $favorite = new Favorite(new User(), new Recipe());
        $otherUser = new User();
        $otherRecipe = new Recipe();
        $createdAt = new \DateTimeImmutable('2026-01-01');

        $favorite->setUser($otherUser)
            ->setRecipe($otherRecipe)
            ->setCreatedAt($createdAt);

        $this->assertSame($otherUser, $favorite->getUser());
        $this->assertSame($otherRecipe, $favorite->getRecipe());
        $this->assertSame($createdAt, $favorite->getCreatedAt());
    }
}
