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

    /**
     * @param EntityManagerInterface $em
     * @param IngredientRepository $ingredientRepository
     * @param UtensilRepository $utensilRepository
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EntityManagerInterface $em, IngredientRepository $ingredientRepository, UtensilRepository $utensilRepository, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->ingredientRepository = $ingredientRepository;
        $this->utensilRepository = $utensilRepository;
        $this->tokenStorage = $tokenStorage;
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
        $isNew = $recipe->getId() === null;
        if ($isNew) {
            $recipe->setAuthor($this->getUser());
            $recipe->setCreatedAt(new \DateTimeImmutable());
        } else {
            $recipe->setUpdatedAt(new \DateTimeImmutable());
        }
        $this->em->persist($recipe);

        # Create ingredient if is an id (non existant in db)
        foreach ($submittedData['recipe']['ingredients'] as $ingredient) {
            if (!is_numeric($ingredient)) {
                $newIngredient = new Ingredient();
                $newIngredient->setName($ingredient);
                $this->em->persist($newIngredient);
                }
            }
        
            $this->em->flush();
            
        # index existing refs by ingredient id
        $existing = [];
        foreach ($recipe->getRefRecipeIngredients() as $ref) {
            $existing[$ref->getIngredient()->getId()] = $ref;
            }
            
        # Persist refRecipeIngredients
        $submittedIds = [];
        foreach (($submittedData['extras'] ?? []) as $ingredient => $datas) {
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
        
        # Drop refs the user removed (orphanRemoval on Recipe handles the DELETE)
        foreach ($existing as $id => $ref) {
            if (!isset($submittedIds[$id])) {
                $recipe->getRefRecipeIngredients()->removeElement($ref);
            }
        }

        # Sync recipe utensils: numeric -> existing; non-numeric -> new entity
        $previousUtensils = [];
        foreach ($recipe->getUtensil() as $u) {
            $previousUtensils[spl_object_id($u)] = $u;
        }
        $keptObjIds = [];
        foreach (($submittedData['recipe']['utensil'] ?? []) as $utensilValue) {
            if (is_numeric($utensilValue)) {
                $utensil = $this->utensilRepository->find($utensilValue);
                if ($utensil === null) {
                    continue;
                }
                $keptObjIds[spl_object_id($utensil)] = true;
            } else {
                $utensil = new Utensil();
                $utensil->setName($utensilValue)
                    ->setCreatedAt(new DateTimeImmutable())
                    ->setCreatedBy($this->getUser());
                $this->em->persist($utensil);
            }
            $recipe->addUtensil($utensil);
        }
        foreach ($previousUtensils as $objId => $u) {
            if (!isset($keptObjIds[$objId])) {
                $recipe->removeUtensil($u);
            }
        }

        $this->em->flush();
    }
}

