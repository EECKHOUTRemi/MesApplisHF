<?php

namespace App\Tests\Functional\Admin\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

/**
 * Teste le CRUD admin des favoris MaCuisine (/admin/macuisine/favorite) ainsi que
 * sa protection par ROLE_ADMIN.
 */
class FavoriteControllerTest extends AppWebTestCase
{
    private const BASE_PATH = '/admin/macuisine/favorite';

    /**
     * @param User $author
     * @param string $prefix
     * @return Recipe
     */
    private function createRecipe(User $author, string $prefix = 'AdmFav'): Recipe
    {
        // Recipe.name est limité à 30 caractères
        $recipe = (new Recipe())
            ->setAuthor($author)
            ->setName(substr($prefix . '-' . bin2hex(random_bytes(6)), 0, 30))
            ->setDescription('Recette de test des favoris admin.')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($recipe);
        $this->em()->flush();

        return $recipe;
    }

    /**
     * @param User|null $user
     * @return Favorite
     */
    private function createFavorite(?User $user = null): Favorite
    {
        $user ??= $this->createUser();
        $favorite = new Favorite($user, $this->createRecipe($user));

        $this->em()->persist($favorite);
        $this->em()->flush();

        return $favorite;
    }

    public function testIndexListsFavorites(): void
    {
        $favorite = $this->createFavorite();
        $this->login($this->createAdmin());

        $this->client->request('GET', self::BASE_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $favorite->getRecipe()->getName());
    }

    public function testShowDisplaysTheFavorite(): void
    {
        $favorite = $this->createFavorite();
        $this->login($this->createAdmin());

        $this->client->request('GET', self::BASE_PATH . '/' . $favorite->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $favorite->getRecipe()->getName());
        $this->assertSelectorTextContains('body', $favorite->getUser()->getUsername());
    }

    public function testNewCreatesAFavorite(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($admin);

        $crawler = $this->client->request('GET', self::BASE_PATH . '/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'favorite[createdAt]' => '2026-08-25T10:00',
            'favorite[user]' => (string) $user->getId(),
            'favorite[recipe]' => (string) $recipe->getId(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE_PATH);

        $created = $this->em()->getRepository(Favorite::class)
            ->findOneBy(['user' => $user->getId(), 'recipe' => $recipe->getId()]);
        $this->assertNotNull($created);
    }

    public function testEditUpdatesTheFavorite(): void
    {
        $favorite = $this->createFavorite();
        $otherRecipe = $this->createRecipe($favorite->getUser(), 'Modif');
        $this->login($this->createAdmin());

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $favorite->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Mettre à jour')->form([
            'favorite[recipe]' => (string) $otherRecipe->getId(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE_PATH);

        $this->em()->clear();
        $updated = $this->em()->getRepository(Favorite::class)->find($favorite->getId());
        $this->assertSame($otherRecipe->getId(), $updated->getRecipe()->getId());
    }

    public function testDeleteRemovesTheFavorite(): void
    {
        $favorite = $this->createFavorite();
        $id = $favorite->getId();
        $this->login($this->createAdmin());

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $id);
        $this->client->submit($crawler->filter('form[method="post"]')->form());

        $this->assertResponseRedirects(self::BASE_PATH);

        $this->em()->clear();
        $this->assertNull($this->em()->getRepository(Favorite::class)->find($id));
    }

    public function testDeleteWithoutAValidCsrfTokenKeepsTheFavorite(): void
    {
        $favorite = $this->createFavorite();
        $id = $favorite->getId();
        $this->login($this->createAdmin());

        $this->client->request('POST', self::BASE_PATH . '/' . $id, ['_token' => 'invalide']);

        $this->assertResponseRedirects(self::BASE_PATH);

        $this->em()->clear();
        $this->assertNotNull($this->em()->getRepository(Favorite::class)->find($id));
    }

    public function testAStandardUserCannotReachTheAdminPanel(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::BASE_PATH);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->request('GET', self::BASE_PATH);

        $this->assertResponseRedirects('/login');
    }
}
