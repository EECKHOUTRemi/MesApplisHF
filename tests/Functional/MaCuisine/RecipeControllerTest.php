<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\MaCuisine\RefRecipeIngredient;
use App\Entity\MaCuisine\Utensil;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Teste le CRUD utilisateur des recettes MaCuisine, incluant la synchronisation
 * des ingrédients/ustensiles via RecipeFormHandler (soumission directe de la requête
 * pour contourner les selects JS dynamiques).
 */
class RecipeControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/macuisine';
    // le fil des recettes
    private const RECIPES_PATH = self::INDEX_PATH . '/feed';
    // cible de redirection après création/édition/suppression (tableau de bord)
    private const REDIRECT_PATH = self::INDEX_PATH;

    /**
     * @param User $author
     * @param string $name
     * @return Recipe
     */
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

    /**
     * @param string $prefix
     * @return string
     */
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

        $this->client->request('GET', self::RECIPES_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.post-title', $recipe->getName());
    }

    public function testShowDisplaysRecipe(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, $this->uniqueName('Gratin-'));
        $this->login($user);

        $this->client->request('GET', self::INDEX_PATH . '/' . $recipe->getId());

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

        $crawler = $this->client->request('GET', self::INDEX_PATH . '/mine');

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
        $this->client->request('GET', self::INDEX_PATH . '/ajax/ingredients', ['term' => 'echalote-' . $suffix]);

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
        $crawler = $this->client->request('GET', self::RECIPES_PATH);
        $form = $crawler->filter('form[action="' . self::INDEX_PATH . '/' . $recipeId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->em()->clear();
        $this->assertNull($this->em()->find(Recipe::class, $recipeId));
    }

    public function testDeleteRemovesStoredImageFile(): void
    {
        $user = $this->createUser();
        $recipe = $this->createRecipe($user, $this->uniqueName('SupprImg-'));

        $dir = $this->recipesImagesDirectory();
        $filesystem = new Filesystem();
        $filesystem->mkdir($dir);
        $image = 'del_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($dir . '/' . $image, 'fake-image');
        $recipe->setImage($image);
        $this->em()->flush();

        $recipeId = $recipe->getId();
        $this->login($user);

        $crawler = $this->client->request('GET', self::RECIPES_PATH);
        $form = $crawler->filter('form[action="' . self::INDEX_PATH . '/' . $recipeId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->em()->clear();
        $this->assertNull($this->em()->find(Recipe::class, $recipeId));

        // le fichier image de la recette est supprimé avec elle
        $this->assertFileDoesNotExist($dir . '/' . $image);
    }

    /**
     * @param string $name
     * @return Ingredient
     */
    private function createIngredient(string $name): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setName($name);
        $this->em()->persist($ingredient);
        $this->em()->flush();

        return $ingredient;
    }

    /**
     * @param User $user
     * @param string $name
     * @return Utensil
     */
    private function createUtensil(User $user, string $name): Utensil
    {
        $utensil = new Utensil();
        $utensil->setName($name);
        $utensil->setCreatedAt(new \DateTimeImmutable());
        $utensil->setCreatedBy($user);
        $this->em()->persist($utensil);
        $this->em()->flush();

        return $utensil;
    }

    /**
     * Le formulaire recette est dynamique (selects remplis en JS) : on poste
     * directement la requête, comme le navigateur le ferait, avec le token
     * CSRF récupéré sur la page.
     *
     * @param string $path
     * @return string
     */
    private function csrfToken(string $path): string
    {
        $crawler = $this->client->request('GET', $path);
        $this->assertResponseIsSuccessful();

        return $crawler->filter('input[name="recipe[_token]"]')->attr('value');
    }

    /** @return string */
    private function recipesImagesDirectory(): string
    {
        return (string) static::getContainer()->getParameter('recipes_images_directory');
    }

    /**
     * Construit un upload PNG 1x1 valide : la contrainte File vérifie le type réel
     * du fichier, on a donc besoin d'octets PNG authentiques, pas d'un fichier vide.
     *
     * @param string $originalName
     * @return UploadedFile
     */
    private function pngUpload(string $originalName = 'photo.png'): UploadedFile
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
        );
        $path = tempnam(sys_get_temp_dir(), 'recipe_png_');
        file_put_contents($path, $bytes);

        // dernier argument à true : mode test, court-circuite la vérification is_uploaded_file()
        return new UploadedFile($path, $originalName, 'image/png', null, true);
    }

    public function testNewCreatesRecipeWithIngredientsAndUtensils(): void
    {
        $user = $this->createUser();
        $this->login($user);
        $suffix = bin2hex(random_bytes(4));

        $tomate = $this->createIngredient('Tomate-' . $suffix);
        $fouet = $this->createUtensil($user, 'Fouet-' . $suffix);
        $category = new Category();
        $category->setName('Plat-' . $suffix);
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setCreatedBy($user);
        $this->em()->persist($category);
        $this->em()->flush();

        $name = $this->uniqueName('Complete-');
        $this->client->request('POST', self::INDEX_PATH . '/new', [
            'recipe' => [
                '_token' => $this->csrfToken(self::INDEX_PATH . '/new'),
                'name' => $name,
                'description' => 'Recette complète : ingrédients, ustensiles, catégorie.',
                'category' => (string) $category->getId(),
                // valeur numérique -> ustensile existant ; texte -> à créer
                'utensil' => [(string) $fouet->getId(), 'Cuillere-' . $suffix],
                'ingredients' => [(string) $tomate->getId(), 'Basilic-' . $suffix],
            ],
            'extras' => [
                (string) $tomate->getId() => ['qty' => '500', 'unit' => 'g'],
                'Basilic-' . $suffix => ['qty' => '10', 'unit' => 'feuilles'],
            ],
        ]);

        $this->assertResponseRedirects(self::REDIRECT_PATH);

        $this->em()->clear();
        $recipe = $this->em()->getRepository(Recipe::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($recipe);
        $this->assertSame($user->getId(), $recipe->getAuthor()->getId());
        $this->assertSame('Plat-' . $suffix, $recipe->getCategory()->getName());

        $refs = $recipe->getRefRecipeIngredients();
        $this->assertCount(2, $refs);
        $byName = [];
        foreach ($refs as $ref) {
            $byName[$ref->getIngredient()->getName()] = $ref;
        }
        $this->assertSame(500.0, $byName['Tomate-' . $suffix]->getQuantity());
        $this->assertSame('g', $byName['Tomate-' . $suffix]->getUnite());
        $this->assertSame(10.0, $byName['Basilic-' . $suffix]->getQuantity());

        $utensilNames = array_map(fn (Utensil $u) => $u->getName(), $recipe->getUtensil()->toArray());
        $this->assertEqualsCanonicalizing(['Fouet-' . $suffix, 'Cuillere-' . $suffix], $utensilNames);
    }

    public function testEditSyncsIngredientsAndUtensils(): void
    {
        $user = $this->createUser();
        $this->login($user);
        $suffix = bin2hex(random_bytes(4));

        $farine = $this->createIngredient('Farine-' . $suffix);
        $sucre = $this->createIngredient('Sucre-' . $suffix);
        $rouleau = $this->createUtensil($user, 'Rouleau-' . $suffix);
        $maryse = $this->createUtensil($user, 'Maryse-' . $suffix);

        $recipe = $this->createRecipe($user, $this->uniqueName('Sync-'));
        $recipe->addUtensil($rouleau);
        foreach ([[$farine, 200.0], [$sucre, 100.0]] as [$ingredient, $qty]) {
            $ref = new RefRecipeIngredient();
            $ref->setRecipe($recipe);
            $ref->setIngredient($ingredient);
            $ref->setQuantity($qty);
            $ref->setUnite('g');
            $this->em()->persist($ref);
        }
        $this->em()->flush();
        $editPath = self::INDEX_PATH . '/' . $recipe->getId() . '/edit';

        // garde la farine (quantité modifiée), retire le sucre,
        // remplace le rouleau par la maryse (l'id inconnu doit être ignoré)
        $this->client->request('POST', $editPath, [
            'recipe' => [
                '_token' => $this->csrfToken($editPath),
                'name' => $recipe->getName(),
                'description' => $recipe->getDescription(),
                'category' => '',
                'utensil' => [(string) $maryse->getId(), '999999999'],
                'ingredients' => [(string) $farine->getId()],
            ],
            'extras' => [
                (string) $farine->getId() => ['qty' => '250', 'unit' => 'g'],
            ],
        ]);

        $this->assertResponseRedirects(self::REDIRECT_PATH);

        $this->em()->clear();
        $reloaded = $this->em()->find(Recipe::class, $recipe->getId());
        $this->assertNotNull($reloaded->getUpdatedAt());

        $refs = $reloaded->getRefRecipeIngredients();
        $this->assertCount(1, $refs);
        $this->assertSame('Farine-' . $suffix, $refs[0]->getIngredient()->getName());
        $this->assertSame(250.0, $refs[0]->getQuantity());

        $utensilNames = array_map(fn (Utensil $u) => $u->getName(), $reloaded->getUtensil()->toArray());
        $this->assertSame(['Maryse-' . $suffix], $utensilNames);
    }

    public function testNewPersistsPlainTextSource(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $name = $this->uniqueName('Source-');
        $source = 'Carnet de recettes de ma grand-mère';
        $this->client->request('POST', self::INDEX_PATH . '/new', [
            'recipe' => [
                '_token' => $this->csrfToken(self::INDEX_PATH . '/new'),
                'name' => $name,
                'description' => 'Une recette avec une source en texte libre.',
                'category' => '',
                'source' => $source,
            ],
        ]);

        $this->assertResponseRedirects(self::REDIRECT_PATH);

        $this->em()->clear();
        $recipe = $this->em()->getRepository(Recipe::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($recipe);
        $this->assertSame($source, $recipe->getSource());
    }

    public function testNewRejectsUrlLikeSource(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $name = $this->uniqueName('BadSrc-');
        $this->client->request('POST', self::INDEX_PATH . '/new', [
            'recipe' => [
                '_token' => $this->csrfToken(self::INDEX_PATH . '/new'),
                'name' => $name,
                'category' => '',
                'source' => 'https://www.marmiton.org/recettes/tarte',
            ],
        ]);

        // formulaire ré-affiché avec l'erreur de validation (422 sur form invalide)
        $this->assertResponseIsUnprocessable();
        $this->assertSelectorTextContains('body', 'La source ne doit pas contenir de lien');

        $this->em()->clear();
        $this->assertNull($this->em()->getRepository(Recipe::class)->findOneBy(['name' => $name]));
    }

    public function testNewStoresUploadedImage(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $name = $this->uniqueName('Image-');
        $this->client->request(
            'POST',
            self::INDEX_PATH . '/new',
            [
                'recipe' => [
                    '_token' => $this->csrfToken(self::INDEX_PATH . '/new'),
                    'name' => $name,
                    'category' => '',
                ],
            ],
            [
                'recipe' => ['image' => $this->pngUpload()],
            ],
        );

        $this->assertResponseRedirects(self::REDIRECT_PATH);

        $this->em()->clear();
        $recipe = $this->em()->getRepository(Recipe::class)->findOneBy(['name' => $name]);
        $this->assertNotNull($recipe);
        $this->assertStringEndsWith('.png', (string) $recipe->getImage());

        $storedFile = $this->recipesImagesDirectory() . '/' . $recipe->getImage();
        $this->assertFileExists($storedFile);

        // nettoyage du fichier déposé pendant le test
        (new Filesystem())->remove($storedFile);
    }

    public function testEditReplacesImageAndRemovesOldFile(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $dir = $this->recipesImagesDirectory();
        $filesystem = new Filesystem();
        $filesystem->mkdir($dir);
        $oldImage = 'old_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($dir . '/' . $oldImage, 'fake-old-image');

        $recipe = $this->createRecipe($user, $this->uniqueName('EditImg-'));
        $recipe->setImage($oldImage);
        $this->em()->flush();

        $editPath = self::INDEX_PATH . '/' . $recipe->getId() . '/edit';
        $this->client->request(
            'POST',
            $editPath,
            [
                'recipe' => [
                    '_token' => $this->csrfToken($editPath),
                    'name' => $recipe->getName(),
                    'category' => '',
                ],
            ],
            [
                'recipe' => ['image' => $this->pngUpload()],
            ],
        );

        $this->assertResponseRedirects(self::REDIRECT_PATH);

        $this->em()->clear();
        $reloaded = $this->em()->find(Recipe::class, $recipe->getId());
        $newImage = (string) $reloaded->getImage();
        $this->assertNotSame($oldImage, $newImage);
        $this->assertStringEndsWith('.png', $newImage);

        // l'ancien fichier est supprimé, le nouveau est bien déposé
        $this->assertFileDoesNotExist($dir . '/' . $oldImage);
        $this->assertFileExists($dir . '/' . $newImage);

        $filesystem->remove($dir . '/' . $newImage);
    }
}
