<?php

namespace App\Handler;

use App\Entity\MaCuisine\Recipe;
use App\Repository\MaCuisine\CategoryRepository;
use App\Repository\MaCuisine\FavoriteRepository;
use App\Repository\MaCuisine\UtensilRepository;

class RecipeFeedOptionProvider
{
    /**
     * Options communes du fil (filtres catégories + ustensiles, favoris de l'utilisateur)
     * injectées dans le template.
     *
     * @param CategoryRepository $categoryRepository
     * @param UtensilRepository $utensilRepository
     * @param FavoriteRepository $favoriteRepository
     * @param Recipe[] $recipes
     * @return array<string, mixed>
     */
    public function feedFilterOptions(
        CategoryRepository $categoryRepository,
        UtensilRepository $utensilRepository,
        FavoriteRepository $favoriteRepository,
        array $recipes
    ): array {
        return [
            'categories' => $categoryRepository->findAll(),
            'utensils' => array_map(
                fn ($u) => ['id' => $u->getId(), 'name' => $u->getName()],
                $utensilRepository->findAll()
            ),
            'favoriteIds' => $favoriteRepository->findRecipeIdsForConnectedUser(),
            'favoriteCounts' => $favoriteRepository->countByRecipes($recipes),
        ];
    }
}
