<?php

namespace App\Handler;

use App\Entity\macuisine\Ingredient;
use App\Entity\macuisine\Recipe;
use App\Entity\macuisine\RefRecipeIngredient;
use App\Repository\macuisine\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RecipeFormHandler
{
    private EntityManagerInterface $em;
    private IngredientRepository $ingredientRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(EntityManagerInterface $em, IngredientRepository $ingredientRepository, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->ingredientRepository = $ingredientRepository;
        $this->tokenStorage = $tokenStorage;
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        return $token ? $token->getUser() : null;
    }

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

        // index existing refs by ingredient id
        $existing = [];
        foreach ($recipe->getRefRecipeIngredients() as $ref) {
            $existing[$ref->getIngredient()->getId()] = $ref;
        }

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

        // drop refs the user removed (orphanRemoval on Recipe handles the DELETE)
        foreach ($existing as $id => $ref) {
            if (!isset($submittedIds[$id])) {
                $recipe->getRefRecipeIngredients()->removeElement($ref);
            }
        }

        $this->em->flush();
    }
}

