<?php

namespace App\Handler;

use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\MaCuisine\RefRecipeIngredient;
use App\Entity\MaCuisine\Utensil;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\UtensilRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Orchestre la persistance d'une recette depuis le formulaire utilisateur.
 * Gère la création à la volée d'ingrédients/ustensiles inconnus (valeurs non numériques),
 * la synchronisation des RefRecipeIngredient (ajout, mise à jour, suppression des orphelins)
 * et le remplacement de la liste d'ustensiles de la recette.
 */
class RecipeFormHandler
{
    private EntityManagerInterface $em;
    private IngredientRepository $ingredientRepository;
    private UtensilRepository $utensilRepository;
    private TokenStorageInterface $tokenStorage;
    private string $recipesImagesDirectory;

    /**
     * @param EntityManagerInterface $em
     * @param IngredientRepository $ingredientRepository
     * @param UtensilRepository $utensilRepository
     * @param TokenStorageInterface $tokenStorage
     * @param string $recipesImagesDirectory Chemin absolu du dossier de stockage des images de recettes
     */
    public function __construct(
        EntityManagerInterface $em,
        IngredientRepository $ingredientRepository,
        UtensilRepository $utensilRepository,
        TokenStorageInterface $tokenStorage,
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory,
    ) {
        $this->em = $em;
        $this->ingredientRepository = $ingredientRepository;
        $this->utensilRepository = $utensilRepository;
        $this->tokenStorage = $tokenStorage;
        $this->recipesImagesDirectory = $recipesImagesDirectory;
    }

    /**
     * Retourne l'utilisateur courant depuis le token de sécurité, ou null si non authentifié.
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface|null
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        return $token ? $token->getUser() : null;
    }

    /**
     * Persiste la recette avec ses ingrédients et ses ustensiles depuis les données brutes du formulaire.
     *
     * @param Recipe  $recipe        Entité à créer ou mettre à jour
     * @param array<string, mixed> $submittedData Données brutes de la requête (clés `recipe` et `extras`)
     * @return void
     */
    public function persistAndFlush(Recipe $recipe, array $submittedData)
    {
        $this->stampRecipe($recipe);
        $this->em->persist($recipe);

        if (isset($submittedData['recipe']['ingredients'])) {
            $this->createNewIngredients($submittedData['recipe']['ingredients']);
            $this->em->flush();
            $this->syncIngredientRefs($recipe, $submittedData['extras'] ?? []);
        }

        if (isset($submittedData['recipe']['utensil'])) {
            $this->syncUtensils($recipe, $submittedData['recipe']['utensil'] ?? []);
        }

        $this->em->flush();
    }

    /**
     * Set the author/createdAt on a new recipe, or the updatedAt on an existing one.
     *
     * @param Recipe $recipe
     * @return void
     */
    private function stampRecipe(Recipe $recipe): void
    {
        if ($recipe->getId() === null) {
            $recipe->setAuthor($this->getUser());
            $recipe->setCreatedAt(new \DateTimeImmutable());
        } else {
            $recipe->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    /**
     * Persist a new Ingredient for each non-numeric (not yet in db) value.
     *
     * @param array $ingredients
     * @return void
     */
    private function createNewIngredients(array $ingredients): void
    {
        foreach ($ingredients as $ingredient) {
            if (!is_numeric($ingredient)) {
                $newIngredient = new Ingredient();
                $newIngredient->setName($ingredient);
                $this->em->persist($newIngredient);
            }
        }
    }

    /**
     * Create or update the recipe's ingredient refs from the submitted extras,
     * dropping the refs the user removed (orphanRemoval handles the DELETE).
     *
     * @param Recipe $recipe
     * @param array $extras
     * @return void
     */
    private function syncIngredientRefs(Recipe $recipe, array $extras): void
    {
        # index existing refs by ingredient id
        $existing = [];
        foreach ($recipe->getRefRecipeIngredients() as $ref) {
            $existing[$ref->getIngredient()->getId()] = $ref;
        }

        $submittedIds = [];
        foreach ($extras as $ingredient => $datas) {
            $ingredientObj = is_numeric($ingredient)
                ? $this->ingredientRepository->find($ingredient)
                : $this->ingredientRepository->findNameLike($ingredient)[0];

            $id = $ingredientObj->getId();
            $submittedIds[$id] = true;

            $ref = $existing[$id] ?? (new RefRecipeIngredient())
                ->setRecipe($recipe)
                ->setIngredient($ingredientObj);

            $ref->setUnite($datas['unit']);
            $ref->setQuantity((int) $datas['qty'] ?? null);

            if (!isset($existing[$id])) {
                $this->em->persist($ref);
            }
        }

        foreach ($existing as $id => $ref) {
            if (!isset($submittedIds[$id])) {
                $recipe->getRefRecipeIngredients()->removeElement($ref);
            }
        }
    }

    /**
     * Sync recipe utensils: numeric -> existing entity; non-numeric -> new entity.
     *
     * @param Recipe $recipe
     * @param array $submittedUtensils
     * @return void
     */
    private function syncUtensils(Recipe $recipe, array $submittedUtensils): void
    {
        $previousUtensils = [];
        foreach ($recipe->getUtensil() as $u) {
            $previousUtensils[spl_object_id($u)] = $u;
        }

        $keptObjIds = [];
        foreach ($submittedUtensils as $utensilValue) {
            $utensil = $this->resolveUtensil($utensilValue);
            if ($utensil === null) {
                continue;
            }
            if (is_numeric($utensilValue)) {
                $keptObjIds[spl_object_id($utensil)] = true;
            }
            $recipe->addUtensil($utensil);
        }

        foreach ($previousUtensils as $objId => $u) {
            if (!isset($keptObjIds[$objId])) {
                $recipe->removeUtensil($u);
            }
        }
    }

    /**
     * Resolve a submitted utensil value to an entity: look up a numeric id, or
     * create and persist a new Utensil for a free-text value.
     *
     * @param mixed $utensilValue
     * @return Utensil|null
     */
    private function resolveUtensil($utensilValue): ?Utensil
    {
        if (is_numeric($utensilValue)) {
            return $this->utensilRepository->find($utensilValue);
        }

        $utensil = new Utensil();
        $utensil->setName($utensilValue)
            ->setCreatedAt(new DateTimeImmutable())
            ->setCreatedBy($this->getUser());
        $this->em->persist($utensil);

        return $utensil;
    }

    /**
     * Déplace l'image téléversée dans le dossier des recettes, enregistre son nom
     * sur la recette, et supprime l'ancien fichier le cas échéant (mise à jour).
     *
     * @param UploadedFile $imageFile    Fichier image validé issu du formulaire
     * @param Recipe       $recipe       Recette à mettre à jour avec le nouveau nom de fichier
     * @param string|null  $currentImage Nom de l'image à remplacer, ou null lors d'une création
     * @return void
     */
    public function addImage(UploadedFile $imageFile, Recipe $recipe, ?string $currentImage = null): void
    {
        if ($currentImage !== null) {
            (new Filesystem())->remove($this->recipesImagesDirectory . '/' . $currentImage);
        }
        
        $newFilename = uniqid() . '.' . strtolower($imageFile->getClientOriginalExtension());
        $imageFile->move($this->recipesImagesDirectory, $newFilename);
        $recipe->setImage($newFilename);
    }
}

