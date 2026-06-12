<?php

namespace App\Tests\Functional\Admin;

use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

class RecipeAdminControllerTest extends AppWebTestCase
{
    private const BASE_PATH = '/admin/macuisine/recipe';

    private function createRecipe(User $author, string $name): Recipe
    {
        $recipe = new Recipe();
        $recipe->setName($name);
        $recipe->setDescription('Recette de test côté admin.');
        $recipe->setAuthor($author);
        $recipe->setCreatedAt(new \DateTimeImmutable());

        $em = $this->em();
        $em->persist($recipe);
        $em->flush();

        return $recipe;
    }

    private function uniqueName(string $prefix): string
    {
        return substr($prefix . bin2hex(random_bytes(6)), 0, 30);
    }

    public function testIndexListsRecipes(): void
    {
        $admin = $this->createAdmin();
        $recipe = $this->createRecipe($admin, $this->uniqueName('AdmListe-'));
        $this->login($admin);

        $this->client->request('GET', self::BASE_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $recipe->getName());
    }

    public function testCreatePersistsRecipe(): void
    {
        $admin = $this->createAdmin();
        $this->login($admin);
        $name = $this->uniqueName('AdmCree-');

        $crawler = $this->client->request('GET', self::BASE_PATH . '/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('input[name="recipe_admin[name]"]')->closest('form')->form([
            'recipe_admin[name]' => $name,
            'recipe_admin[description]' => 'Créée depuis le back-office.',
            'recipe_admin[createdAt]' => '2026-06-12T10:00',
            'recipe_admin[author]' => (string) $admin->getId(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE_PATH);

        $saved = $this->em()->getRepository(Recipe::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($saved);
        $this->assertSame($admin->getId(), $saved->getAuthor()->getId());
    }

    public function testShowDisplaysRecipe(): void
    {
        $admin = $this->createAdmin();
        $recipe = $this->createRecipe($admin, $this->uniqueName('AdmDetail-'));
        $this->login($admin);

        $this->client->request('GET', self::BASE_PATH . '/' . $recipe->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $recipe->getName());
    }

    public function testEditUpdatesRecipe(): void
    {
        $admin = $this->createAdmin();
        $recipe = $this->createRecipe($admin, $this->uniqueName('AdmAvant-'));
        $this->login($admin);
        $newName = $this->uniqueName('AdmApres-');

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $recipe->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('input[name="recipe_admin[name]"]')->closest('form')->form([
            'recipe_admin[name]' => $newName,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        $this->assertSame($newName, $this->em()->find(Recipe::class, $recipe->getId())->getName());
    }

    public function testDeleteRemovesRecipe(): void
    {
        $admin = $this->createAdmin();
        $recipe = $this->createRecipe($admin, $this->uniqueName('AdmSuppr-'));
        $recipeId = $recipe->getId();
        $this->login($admin);

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $recipeId);
        $form = $crawler->filter(sprintf('form[action="%s/%d"]', self::BASE_PATH, $recipeId))->form();
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        $this->assertNull($this->em()->find(Recipe::class, $recipeId));
    }
}
