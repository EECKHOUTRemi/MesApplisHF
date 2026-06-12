<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

class RecipeControllerTest extends AppWebTestCase
{
    private function createRecipe(User $author, string $name): Recipe
    {
        $recipe = new Recipe();
        $recipe->setName($name);
        $recipe->setDescription('Une recette de test, simple et rapide.');
        $recipe->setAuthor($author);
        $recipe->setCreatedAt(new \DateTimeImmutable());

        $em = $this->em();
        $em->persist($recipe);
        $em->flush();

        return $recipe;
    }

    private function uniqueName(string $prefix): string
    {
        // Recipe.name est limité à 30 caractères
        return substr($prefix . bin2hex(random_bytes(6)), 0, 30);
    }

    public function testIndexListsRecipes(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, $this->uniqueName('Tarte-'));
        $this->login($user);

        $this->client->request('GET', '/macuisine/recipe');

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.post-title', $recipe->getName());
    }

    public function testShowDisplaysRecipe(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, $this->uniqueName('Gratin-'));
        $this->login($user);

        $this->client->request('GET', '/macuisine/recipe/' . $recipe->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $recipe->getName());
    }

    public function testMineOnlyListsOwnRecipes(): void
    {
        $me = $this->createUser();
        $someoneElse = $this->createUser();
        $myRecipe = $this->createRecipe($me, $this->uniqueName('Mienne-'));
        $otherRecipe = $this->createRecipe($someoneElse, $this->uniqueName('Autre-'));
        $this->login($me);

        $crawler = $this->client->request('GET', '/macuisine/recipe/mine');

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString($myRecipe->getName(), $body);
        $this->assertStringNotContainsString($otherRecipe->getName(), $body);
    }

    public function testIngredientsAjaxSearchIsAccentAndCaseInsensitive(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $ingredient = new Ingredient();
        $ingredient->setName('Échalote-' . $suffix);
        $em = $this->em();
        $em->persist($ingredient);
        $em->flush();

        $this->login($this->createUser());

        // recherche sans accent ni majuscule
        $this->client->request('GET', '/macuisine/recipe/ajax/ingredients', ['term' => 'echalote-' . $suffix]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $results = json_decode($this->client->getResponse()->getContent(), true);
        $names = array_column($results, 'name');
        $this->assertContains($ingredient->getName(), $names);
    }

    public function testDeleteRemovesOwnRecipe(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, $this->uniqueName('Asuppr-'));
        $recipeId = $recipe->getId();
        $this->login($user);

        // le formulaire de suppression (token CSRF inclus) est dans le fil des recettes
        $crawler = $this->client->request('GET', '/macuisine/recipe');
        $form = $crawler->filter('form[action="/macuisine/recipe/' . $recipeId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->em()->clear();
        $this->assertNull($this->em()->find(Recipe::class, $recipeId));
    }
}
