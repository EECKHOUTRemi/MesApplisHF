<?php

namespace App\Controller\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Form\MaCuisine\RecipeType;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RecipeRepository;
use App\Repository\MaCuisine\UtensilRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Handler\RecipeFormHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/macuisine/recipe', name:'app_macuisine_recipe_'),
IsGranted('ROLE_USER')]
final class RecipeController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): Response
    {
        return $this->render('MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findAll(),
            'mine' => false
        ]);
    }

    #[Route('/mine', name:'mine')]
    public function mine(RecipeRepository $recipeRepository){
        return $this->render('MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findBy(['author' => $this->getUser()]),
            'mine' => true
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, RecipeFormHandler $recipeFormHandler, UtensilRepository $utensilRepository): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute('app_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MaCuisine/recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
            'utensils' => array_map(
                fn ($u) => ['id' => $u->getId(), 'name' => $u->getName()],
                $utensilRepository->findAll()
            ),
            'selectedUtensils' => [],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Recipe $recipe): Response
    {
        return $this->render('MaCuisine/recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, RecipeFormHandler $recipeFormHandler, UtensilRepository $utensilRepository): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute('app_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        $refs = $recipe->getRefRecipeIngredients();
        $ingredientsList = [];
        foreach ($refs as $ref) {
            $ingredient = $ref->getIngredient();
            $ingredientsList[$ingredient->getId()] = [
                'name' => $ingredient->getName(),
                'quantity' => $ref->getQuantity(),
                'unit' => $ref->getUnite()
            ];
        }

        $selectedUtensils = [];
        foreach ($recipe->getUtensil() as $u) {
            $selectedUtensils[] = $u->getId();
        }

        return $this->render('MaCuisine/recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
            'ingredients' => $ingredientsList,
            'utensils' => array_map(
                fn ($u) => ['id' => $u->getId(), 'name' => $u->getName()],
                $utensilRepository->findAll()
            ),
            'selectedUtensils' => $selectedUtensils,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajax/ingredients', name: 'ingredients_ajax', methods: ['GET'])]
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
