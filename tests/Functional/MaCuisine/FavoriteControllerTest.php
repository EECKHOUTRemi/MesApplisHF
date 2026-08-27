<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

/**
 * Teste le parcours favoris côté utilisateur : bascule ajout/retrait,
 * page « Mes favoris » et cloisonnement entre comptes.
 */
class FavoriteControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/macuisine/favorite/index';
    private const TOGGLE_PATH = '/macuisine/favorite/toggleFavorite';

    /**
     * @param User $author
     * @param string $prefix
     * @return Recipe
     */
    private function createRecipe(User $author, string $prefix = 'Fav'): Recipe
    {
        // Recipe.name est limité à 30 caractères
        $recipe = (new Recipe())
            ->setAuthor($author)
            ->setName(substr($prefix . '-' . bin2hex(random_bytes(6)), 0, 30))
            ->setDescription('Recette de test des favoris.')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($recipe);
        $this->em()->flush();

        return $recipe;
    }

    /**
     * @param User $user
     * @param Recipe $recipe
     * @return Favorite|null
     */
    private function findFavorite(User $user, Recipe $recipe): ?Favorite
    {
        $this->em()->clear();

        return $this->em()->getRepository(Favorite::class)
            ->findOneBy(['user' => $user->getId(), 'recipe' => $recipe->getId()]);
    }

    public function testToggleAddsTheRecipeToFavorites(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);

        $this->assertResponseRedirects();
        $this->assertNotNull($this->findFavorite($user, $recipe));
    }

    public function testToggleTwiceRemovesTheFavorite(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);
        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);

        $this->assertResponseRedirects();
        $this->assertNull($this->findFavorite($user, $recipe));
    }

    public function testToggleShowsAFlashMessage(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Recette ajoutée à vos favoris');
    }

    /** Sans referer, le retour se fait sur la fiche de la recette basculée. */
    public function testToggleFallsBackToTheRecipePage(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);

        $this->assertResponseRedirects('/macuisine/' . $recipe->getId());
    }

    /** Avec un referer, on revient sur la page d'où vient le clic (fil, favoris…). */
    public function testToggleReturnsToTheRefererPage(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request(
            'POST',
            self::TOGGLE_PATH,
            ['recipeId' => $recipe->getId()],
            [],
            ['HTTP_REFERER' => 'http://localhost/macuisine/feed']
        );

        $this->assertResponseRedirects('http://localhost/macuisine/feed');
    }

    public function testToggleOnAnUnknownRecipeReturns404(): void
    {
        $this->login($this->createUser());

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => 0]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testToggleRejectsGetRequests(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);
        $this->login($user);

        $this->client->request('GET', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testIndexListsOnlyTheConnectedUserFavorites(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $mine = $this->createRecipe($user, 'Mienne');
        $theirs = $this->createRecipe($other, 'Autre');

        $this->em()->persist(new Favorite($user, $mine));
        $this->em()->persist(new Favorite($other, $theirs));
        $this->em()->flush();

        $this->login($user);
        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $mine->getName());
        $this->assertSelectorTextNotContains('body', $theirs->getName());
    }

    public function testIndexIsReachableWithoutAnyFavorite(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseRedirects('/login');
    }

    public function testAnonymousUserCannotToggle(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user);

        $this->client->request('POST', self::TOGGLE_PATH, ['recipeId' => $recipe->getId()]);

        $this->assertResponseRedirects('/login');
        $this->assertNull($this->findFavorite($user, $recipe));
    }
}
