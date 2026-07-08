<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\MaCuisine\RefRecipeIngredient;
use App\Entity\MaCuisine\Utensil;
use App\Entity\User;
use App\Repository\MaCuisine\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Teste RecipeRepository::findWithQuery() : recherche par nom et filtres
 * (ingrédients, ustensiles, catégories), combinaison en ET, sémantique « au moins un »
 * à l'intérieur d'un filtre multivalué, dédoublonnage et tri par date décroissante.
 *
 * Jeu de données isolé par un suffixe aléatoire : les filtres par identifiant ne
 * remontent que les recettes de ce test, ceux par nom sont restreints au suffixe.
 */
class RecipeRepositoryTest extends KernelTestCase
{
    private RecipeRepository $repository;
    private EntityManagerInterface $em;
    private string $suffix;

    /** @var array<string, Recipe> */
    private array $recipes = [];
    /** @var array<string, int> */
    private array $ingredientIds = [];
    /** @var array<string, int> */
    private array $utensilIds = [];
    /** @var array<string, int> */
    private array $categoryIds = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(RecipeRepository::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->suffix = bin2hex(random_bytes(4));

        $author = $this->createUser();

        $tomate = $this->createIngredient('Tomate-' . $this->suffix);
        $basilic = $this->createIngredient('Basilic-' . $this->suffix);
        $this->ingredientIds = ['tomate' => $tomate->getId(), 'basilic' => $basilic->getId()];

        $poele = $this->createUtensil($author, 'Poele-' . $this->suffix);
        $fouet = $this->createUtensil($author, 'Fouet-' . $this->suffix);
        $this->utensilIds = ['poele' => $poele->getId(), 'fouet' => $fouet->getId()];

        $plat = $this->createCategory($author, 'Plat-' . $this->suffix);
        $dessert = $this->createCategory($author, 'Dessert-' . $this->suffix);
        $this->categoryIds = ['plat' => $plat->getId(), 'dessert' => $dessert->getId()];

        // r1 le plus ancien, r3 le plus récent (pour vérifier le tri décroissant)
        $base = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->recipes['r1'] = $this->createRecipe(
            $author,
            'P1-' . $this->suffix,
            $base->modify('-2 hours'),
            [$tomate, $basilic],
            [$poele],
            $plat
        );

        $this->recipes['r2'] = $this->createRecipe(
            $author,
            'P2-' . $this->suffix,
            $base->modify('-1 hour'),
            [$tomate],
            [$fouet],
            $dessert
        );

        $this->recipes['r3'] = $this->createRecipe(
            $author,
            'P3-' . $this->suffix,
            $base,
            [$basilic],
            [],
            $plat
        );
    }

    public function testFilterByNameReturnsMatchingRecipe(): void
    {
        $results = $this->repository->findWithQuery('P1-' . $this->suffix, null, null, null);

        $this->assertSame([$this->recipes['r1']->getId()], $this->ids($results));
    }

    public function testUnknownNameReturnsNoResults(): void
    {
        $results = $this->repository->findWithQuery('introuvable-' . $this->suffix, null, null, null);

        $this->assertSame([], $results);
    }

    public function testFilterByIngredientReturnsRecipesContainingIt(): void
    {
        $results = $this->repository->findWithQuery(null, [$this->ingredientIds['tomate']], null, null);

        $this->assertEqualsCanonicalizing(
            [$this->recipes['r1']->getId(), $this->recipes['r2']->getId()],
            $this->ids($results)
        );
    }

    public function testMultipleIngredientsMatchAnyWithoutDuplicates(): void
    {
        $results = $this->repository->findWithQuery(
            null,
            [$this->ingredientIds['tomate'], $this->ingredientIds['basilic']],
            null,
            null
        );

        $ids = $this->ids($results);
        $this->assertEqualsCanonicalizing(
            [$this->recipes['r1']->getId(), $this->recipes['r2']->getId(), $this->recipes['r3']->getId()],
            $ids
        );
        // r1 contient les deux ingrédients mais ne doit apparaître qu'une fois (distinct)
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function testFilterByUtensilReturnsRecipesUsingIt(): void
    {
        $results = $this->repository->findWithQuery(null, null, [$this->utensilIds['poele']], null);

        $this->assertSame([$this->recipes['r1']->getId()], $this->ids($results));
    }

    public function testFilterByCategoryReturnsRecipesInIt(): void
    {
        $results = $this->repository->findWithQuery(null, null, null, [$this->categoryIds['plat']]);

        $this->assertEqualsCanonicalizing(
            [$this->recipes['r1']->getId(), $this->recipes['r3']->getId()],
            $this->ids($results)
        );
    }

    public function testFiltersAreCombinedWithAnd(): void
    {
        // ingrédient « tomate » ET catégorie « plat » : seule r1 satisfait les deux
        $results = $this->repository->findWithQuery(
            null,
            [$this->ingredientIds['tomate']],
            null,
            [$this->categoryIds['plat']]
        );

        $this->assertSame([$this->recipes['r1']->getId()], $this->ids($results));
    }

    public function testResultsAreOrderedByCreatedAtDescending(): void
    {
        $results = $this->repository->findWithQuery($this->suffix, null, null, null);

        $this->assertSame(
            [$this->recipes['r3']->getId(), $this->recipes['r2']->getId(), $this->recipes['r1']->getId()],
            $this->ids($results)
        );
    }

    public function testEmptyArrayFiltersBehaveAsNoFilter(): void
    {
        $results = $this->repository->findWithQuery($this->suffix, [], [], []);

        $this->assertEqualsCanonicalizing(
            [$this->recipes['r1']->getId(), $this->recipes['r2']->getId(), $this->recipes['r3']->getId()],
            $this->ids($results)
        );
    }

    /**
     * @param Recipe[] $recipes
     * @return int[]
     */
    private function ids(array $recipes): array
    {
        return array_map(static fn (Recipe $r) => $r->getId(), $recipes);
    }

    /** @return User */
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail(uniqid('recipe.search.', true) . '@test.local');
        $user->setUsername('chef');
        $user->setPassword('x');
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param string $name
     * @return Ingredient
     */
    private function createIngredient(string $name): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setName($name);

        $this->em->persist($ingredient);
        $this->em->flush();

        return $ingredient;
    }

    /**
     * @param User $author
     * @param string $name
     * @return Utensil
     */
    private function createUtensil(User $author, string $name): Utensil
    {
        $utensil = new Utensil();
        $utensil->setName($name);
        $utensil->setCreatedAt(new \DateTimeImmutable());
        $utensil->setCreatedBy($author);

        $this->em->persist($utensil);
        $this->em->flush();

        return $utensil;
    }

    /**
     * @param User $author
     * @param string $name
     * @return Category
     */
    private function createCategory(User $author, string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setCreatedBy($author);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    /**
     * @param User $author
     * @param string $name
     * @param \DateTimeImmutable $createdAt
     * @param Ingredient[] $ingredients
     * @param Utensil[] $utensils
     * @param Category|null $category
     * @return Recipe
     */
    private function createRecipe(
        User $author,
        string $name,
        \DateTimeImmutable $createdAt,
        array $ingredients,
        array $utensils,
        ?Category $category,
    ): Recipe {
        $recipe = new Recipe();
        $recipe->setName($name);
        $recipe->setDescription('Recette de test pour la recherche.');
        $recipe->setAuthor($author);
        $recipe->setCreatedAt($createdAt);
        $recipe->setCategory($category);
        foreach ($utensils as $utensil) {
            $recipe->addUtensil($utensil);
        }
        $this->em->persist($recipe);

        foreach ($ingredients as $ingredient) {
            $ref = new RefRecipeIngredient();
            $ref->setRecipe($recipe);
            $ref->setIngredient($ingredient);
            $ref->setQuantity(1.0);
            $ref->setUnite('u');
            $this->em->persist($ref);
        }
        $this->em->flush();

        return $recipe;
    }
}
