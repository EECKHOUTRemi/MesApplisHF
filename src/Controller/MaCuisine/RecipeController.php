<?php

namespace App\Controller\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Recipe;
use App\Entity\MaCuisine\RefRecipeIngredient;
use App\Form\MaCuisine\RecipeType;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TypeError;

use function PHPSTORM_META\type;

#[Route('/macuisine/recipe'),
IsGranted('ROLE_USER')]
final class RecipeController extends AbstractController
{
    #[Route(name: 'app_ma_cuisine_recipe_index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): Response
    {
        return $this->render('MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ma_cuisine_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, IngredientRepository $ingredientRepository): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submittedData = $request->request->all();

            $recipe->setAuthor($this->getUser());
            $recipe->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($recipe);
            
            $ingredientsData = $submittedData['recipe']['ingredients'];
            foreach ($ingredientsData as $ingredient) {
                if (!is_numeric($ingredient)) {
                    $newIngredient = new Ingredient;
                    $newIngredient->setName($ingredient);
                    $entityManager->persist($newIngredient);
                }
            }
            $entityManager->flush();

            $extrasData = $submittedData['extras'];
            foreach ($extrasData as $ingredient => $datas) {
                if (is_numeric($ingredient)) {
                    $ingredientObj = $ingredientRepository->find($ingredient);
                } else if (is_string($ingredient)) {
                    $ingredientObj = $ingredientRepository->findNameLike($ingredient)[0];
                } else {
                    throw new TypeError('Should be string, not '.type($ingredient).'.');
                }

                $newRefRecipeIngredient = new RefRecipeIngredient;
                $newRefRecipeIngredient->setIngredient($ingredientObj);
                $newRefRecipeIngredient->setRecipe($recipe);
                $newRefRecipeIngredient->setUnite($datas['unit']);
                $newRefRecipeIngredient->setQuantity($datas['qty']);

                $entityManager->persist($newRefRecipeIngredient);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_ma_cuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MaCuisine/recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ma_cuisine_recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): Response
    {
        return $this->render('MaCuisine/recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ma_cuisine_recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ma_cuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MaCuisine/recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ma_cuisine_recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ma_cuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajax/ingredients', name: 'app_ma_cuisine_recipe_ingredients_ajax', methods: ['GET'])]
    public function ingredientsAjax(Request $request, IngredientRepository $ingredientRepository): JsonResponse
    {
        $term = $request->query->get('term');

        if ($term === null){
            $rawIngredients = $ingredientRepository->findAll();
        } else {
            $rawIngredients = $ingredientRepository->findNameLike($term);
        }
        $handeledIngredients = [];
        foreach ($rawIngredients as $ingredient){
            array_push($handeledIngredients, [
                'id' => $ingredient->getId(),
                'name' => $ingredient->getName(),
            ]);
        }

        return $this->json($handeledIngredients);
    }
}
