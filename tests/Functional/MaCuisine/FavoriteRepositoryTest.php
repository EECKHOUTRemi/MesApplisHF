<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Repository\MaCuisine\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Teste FavoriteRepository : appartenance aux favoris, liste de l'utilisateur
 * connecté et agrégats utilisés par le fil (compteurs, identifiants).
 *
 * Les méthodes « utilisateur connecté » s'appuient sur Security : le jeton est
 * posé directement dans le stockage, sans requête HTTP.
 *
 * Jeu de données isolé : les utilisateurs sont créés à la volée, donc les
 * requêtes filtrées par utilisateur ne remontent que les données de ce test.
 */
class FavoriteRepositoryTest extends KernelTestCase
{
    private FavoriteRepository $repository;
    private EntityManagerInterface $em;
    private User $user;
    private User $other;
    private Recipe $recipe1;
    private Recipe $recipe2;
    private Recipe $recipe3;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(FavoriteRepository::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->user = $this->createUser();
        $this->other = $this->createUser();

        $this->recipe1 = $this->createRecipe($this->user, 'F1');
        $this->recipe2 = $this->createRecipe($this->user, 'F2');
        $this->recipe3 = $this->createRecipe($this->user, 'F3');

        // recipe1 : deux favoris (l'auteur et un autre membre) ; recipe2 : un seul ;
        // recipe3 : aucun.
        $this->createFavorite($this->user, $this->recipe1);
        $this->createFavorite($this->other, $this->recipe1);
        $this->createFavorite($this->user, $this->recipe2);
    }

    /** @return User */
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail(uniqid('fav.', true) . '@test.local');
        $user->setUsername(uniqid('fav.', true));
        $user->setPassword('hash');
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param User $author
     * @param string $prefix
     * @return Recipe
     */
    private function createRecipe(User $author, string $prefix): Recipe
    {
        // Recipe.name est limité à 30 caractères
        $recipe = (new Recipe())
            ->setAuthor($author)
            ->setName(substr($prefix . '-' . bin2hex(random_bytes(6)), 0, 30))
            ->setDescription('Recette de test des favoris.')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($recipe);
        $this->em->flush();

        return $recipe;
    }

    /**
     * @param User $user
     * @param Recipe $recipe
     * @return Favorite
     */
    private function createFavorite(User $user, Recipe $recipe): Favorite
    {
        $favorite = new Favorite($user, $recipe);

        $this->em->persist($favorite);
        $this->em->flush();

        return $favorite;
    }

    /**
     * @param User $user
     * @return void
     */
    private function loginInTokenStorage(User $user): void
    {
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    public function testIsFavoriteIsTrueForAFavoritedRecipe(): void
    {
        $this->assertTrue($this->repository->isFavorite($this->recipe1, $this->user));
    }

    public function testIsFavoriteIsFalseForANonFavoritedRecipe(): void
    {
        $this->assertFalse($this->repository->isFavorite($this->recipe3, $this->user));
    }

    public function testIsFavoriteIsPerUser(): void
    {
        $this->assertFalse($this->repository->isFavorite($this->recipe2, $this->other));
    }

    public function testFindAllForConnectedUserReturnsOnlyTheirFavorites(): void
    {
        $this->loginInTokenStorage($this->user);

        $favorites = $this->repository->findAllForConnectedUser();

        $recipeIds = array_map(fn (Favorite $f) => $f->getRecipe()->getId(), $favorites);
        $this->assertEqualsCanonicalizing(
            [$this->recipe1->getId(), $this->recipe2->getId()],
            $recipeIds
        );
    }

    public function testFindAllForConnectedUserReturnsNothingWithoutFavorites(): void
    {
        $this->loginInTokenStorage($this->createUser());

        $this->assertSame([], $this->repository->findAllForConnectedUser());
    }

    /**
     * Les identifiants doivent être des entiers : le fil teste l'appartenance avec
     * `recipe.id in favoriteIds`, comparaison stricte côté Twig.
     */
    public function testFindRecipeIdsForConnectedUserReturnsIntegers(): void
    {
        $this->loginInTokenStorage($this->user);

        $ids = $this->repository->findRecipeIdsForConnectedUser();

        $this->assertEqualsCanonicalizing([$this->recipe1->getId(), $this->recipe2->getId()], $ids);
        $this->assertSame($ids, array_map('intval', $ids));
    }

    public function testCountByRecipesCountsEveryUser(): void
    {
        $counts = $this->repository->countByRecipes([$this->recipe1, $this->recipe2]);

        $this->assertSame(2, $counts[$this->recipe1->getId()]);
        $this->assertSame(1, $counts[$this->recipe2->getId()]);
    }

    /** Une recette sans favori est absente du tableau : le fil retombe sur 0. */
    public function testCountByRecipesOmitsRecipesWithoutFavorite(): void
    {
        $counts = $this->repository->countByRecipes([$this->recipe3]);

        $this->assertArrayNotHasKey($this->recipe3->getId(), $counts);
    }

    public function testCountByRecipesWithAnEmptyListSkipsTheQuery(): void
    {
        $this->assertSame([], $this->repository->countByRecipes([]));
    }
}
