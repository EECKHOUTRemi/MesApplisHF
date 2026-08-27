<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

/**
 * Teste le fil des recettes (/macuisine/feed) : câblage des nouveaux filtres
 * (difficulté, budget, temps) sur la requête, ainsi que l'affichage des
 * métadonnées et de l'état favori sur les cartes et la fiche recette.
 */
class RecipeFeedTest extends AppWebTestCase
{
    private const INDEX_PATH = '/macuisine';
    private const FEED_PATH = self::INDEX_PATH . '/feed';

    /**
     * @param User $author
     * @param string $prefix
     * @param array{portions?: int, time?: int, difficulty?: int, cost?: int} $meta
     * @return Recipe
     */
    private function createRecipe(User $author, string $prefix, array $meta = []): Recipe
    {
        // Recipe.name est limité à 30 caractères
        $recipe = (new Recipe())
            ->setAuthor($author)
            ->setName(substr($prefix . '-' . bin2hex(random_bytes(6)), 0, 30))
            ->setDescription('Recette de test du fil.')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPortions($meta['portions'] ?? null)
            ->setTime($meta['time'] ?? null)
            ->setDifficulty($meta['difficulty'] ?? null)
            ->setCost($meta['cost'] ?? null);

        $this->em()->persist($recipe);
        $this->em()->flush();

        return $recipe;
    }

    public function testFeedFiltersByMaxDifficulty(): void
    {
        $user = $this->createUser();
        $facile = $this->createRecipe($user, 'Facile', ['difficulty' => 1]);
        $dure = $this->createRecipe($user, 'Dure', ['difficulty' => 5]);
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['difficulty' => '2']);

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString($facile->getName(), $body);
        $this->assertStringNotContainsString($dure->getName(), $body);
    }

    public function testFeedFiltersByMaxCost(): void
    {
        $user = $this->createUser();
        $bonMarche = $this->createRecipe($user, 'BonMarche', ['cost' => 1]);
        $onereuse = $this->createRecipe($user, 'Onereuse', ['cost' => 5]);
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['cost' => '2']);

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString($bonMarche->getName(), $body);
        $this->assertStringNotContainsString($onereuse->getName(), $body);
    }

    public function testFeedFiltersByMaxTime(): void
    {
        $user = $this->createUser();
        $rapide = $this->createRecipe($user, 'Rapide', ['time' => 15]);
        $longue = $this->createRecipe($user, 'Longue', ['time' => 240]);
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['time' => '30']);

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString($rapide->getName(), $body);
        $this->assertStringNotContainsString($longue->getName(), $body);
    }

    /** Les trois filtres se combinent en ET. */
    public function testFeedCombinesTheNewFilters(): void
    {
        $user = $this->createUser();
        $ok = $this->createRecipe($user, 'Combi', ['difficulty' => 1, 'cost' => 1, 'time' => 15]);
        $tropChere = $this->createRecipe($user, 'Chere', ['difficulty' => 1, 'cost' => 5, 'time' => 15]);
        $this->login($user);

        $crawler = $this->client->request(
            'GET',
            self::FEED_PATH,
            ['difficulty' => '2', 'cost' => '2', 'time' => '30']
        );

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString($ok->getName(), $body);
        $this->assertStringNotContainsString($tropChere->getName(), $body);
    }

    /** Une valeur vide (option « Toutes les difficultés ») ne doit pas filtrer. */
    public function testEmptyFilterValuesAreIgnored(): void
    {
        $user = $this->createUser();
        $sansMeta = $this->createRecipe($user, 'SansMeta');
        $this->login($user);

        $crawler = $this->client->request(
            'GET',
            self::FEED_PATH,
            ['difficulty' => '', 'cost' => '', 'time' => '']
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($sansMeta->getName(), $crawler->filter('body')->text());
    }

    /** Difficulté, budget et temps sont des plafonds : une recette sans valeur est écartée. */
    public function testRecipesWithoutMetaAreHiddenByTheNewFilters(): void
    {
        $user = $this->createUser();
        $sansMeta = $this->createRecipe($user, 'SansMeta');
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['difficulty' => '5']);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString($sansMeta->getName(), $crawler->filter('body')->text());
    }

    public function testFeedDisplaysTheRecipeMetaPills(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, 'Pills', ['portions' => 4, 'time' => 90]);
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['q' => $recipe->getName()]);

        $this->assertResponseIsSuccessful();
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString('4 portions', $body);
        $this->assertStringContainsString('1 h 30', $body);
    }

    public function testFeedShowsTheFavoriteCountAndState(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $recipe = $this->createRecipe($user, 'Compteur');
        $this->em()->persist(new Favorite($user, $recipe));
        $this->em()->persist(new Favorite($other, $recipe));
        $this->em()->flush();

        $this->login($user);
        $crawler = $this->client->request('GET', self::FEED_PATH, ['q' => $recipe->getName()]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.favorite-count', '2 utilisateurs ont mis cette recette en favoris');
        // la recette étant déjà en favori, le bouton propose le retrait
        $this->assertSame(1, $crawler->filter('.post-actions .is-favorite')->count());
    }

    public function testFeedShowsAnEmptyFavoriteCount(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, 'Zero');
        $this->login($user);

        $crawler = $this->client->request('GET', self::FEED_PATH, ['q' => $recipe->getName()]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.favorite-count', "Personne n'a encore mis cette recette en favoris");
        $this->assertSame(0, $crawler->filter('.post-actions .is-favorite')->count());
    }

    public function testMineShowsTheFavoriteState(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, 'MesFav');
        $this->em()->persist(new Favorite($user, $recipe));
        $this->em()->flush();

        $this->login($user);
        $crawler = $this->client->request('GET', self::INDEX_PATH . '/mine');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $crawler->filter('.post-actions .is-favorite')->count());
    }

    public function testShowDisplaysTheFavoriteCountAndMeta(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, 'Fiche', ['portions' => 2, 'difficulty' => 3, 'cost' => 1]);
        $this->em()->persist(new Favorite($user, $recipe));
        $this->em()->flush();

        $this->login($user);
        $crawler = $this->client->request('GET', self::INDEX_PATH . '/' . $recipe->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.favorite-count', '1 utilisateur a mis cette recette en favoris');
        $this->assertStringContainsString('2 portions', $crawler->filter('body')->text());
    }

    public function testShowMarksTheRecipeAsNotFavoritedForAnotherUser(): void
    {
        $author = $this->createUser();
        $visitor = $this->createUser();
        $recipe = $this->createRecipe($author, 'Visiteur');
        $this->em()->persist(new Favorite($author, $recipe));
        $this->em()->flush();

        $this->login($visitor);
        $this->client->request('GET', self::INDEX_PATH . '/' . $recipe->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="/macuisine/favorite/toggleFavorite"] .bi-star');
        $this->assertSelectorNotExists('form[action="/macuisine/favorite/toggleFavorite"] .bi-star-fill');
    }

    public function testFeedShowsABreadcrumb(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::FEED_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav.app-breadcrumb .breadcrumb-item.active');
    }
}
