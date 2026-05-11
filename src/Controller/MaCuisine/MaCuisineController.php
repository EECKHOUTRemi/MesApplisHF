<?php

namespace App\Controller\macuisine;

use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/macuisine', name: 'app_ma_cuisine_'), IsGranted('ROLE_USER')]
final class MaCuisineController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(RecipeRepository $recipeRepository, IngredientRepository $ingredientRepository): Response
    {
        $recentRecipes = $recipeRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        $myRecipesCount = $recipeRepository->count(['author' => $this->getUser()]);

        return $this->render("macuisine/index.html.twig", [
            'recentRecipes' => $recentRecipes,
            'totalRecipes' => $recipeRepository->count([]),
            'totalIngredients' => $ingredientRepository->count([]),
            'myRecipesCount' => $myRecipesCount,
        ]);
    }
}
