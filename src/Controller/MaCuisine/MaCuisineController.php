<?php

namespace App\Controller\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Form\MaCuisine\RecipeType;
use App\Handler\RecipeFormHandler;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RecipeRepository;
use App\Repository\MaCuisine\UtensilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/macuisine', name:'app_macuisine_'),
IsGranted('ROLE_USER')]
/**
 * CRUD des recettes MaCuisine côté utilisateur.
 * L'endpoint AJAX /ajax/ingredients alimente l'autocomplete d'ingrédients dans le formulaire.
 */
final class MaCuisineController extends AbstractController
{
    /**
     * @param RecipeRepository $recipeRepository
     * @param IngredientRepository $ingredientRepository
     * @return Response
     */
    #[Route('/', name: 'index')]
    public function index(RecipeRepository $recipeRepository, IngredientRepository $ingredientRepository): Response
    {
        $recentRecipes = $recipeRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        $myRecipesCount = $recipeRepository->count(['author' => $this->getUser()]);

        return $this->render("MaCuisine/index.html.twig", [
            'recentRecipes' => $recentRecipes,
            'totalRecipes' => $recipeRepository->count([]),
            'totalIngredients' => $ingredientRepository->count([]),
            'myRecipesCount' => $myRecipesCount,
        ]);
    }

    /**
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/recipe', name: 'recipes', methods: ['GET'])]
    public function recipes(RecipeRepository $recipeRepository): Response
    {
        return $this->render('MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findAll(),
            'mine' => false
        ]);
    }

    /**
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/mine', name:'recipe_mine')]
    public function mine(RecipeRepository $recipeRepository){
        return $this->render('MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findBy(['author' => $this->getUser()]),
            'mine' => true
        ]);
    }

    #[Route('/new', name: 'recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, RecipeFormHandler $recipeFormHandler, UtensilRepository $utensilRepository): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $recipeFormHandler->handleImage($imageFile, $recipe);
            }

            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute('app_macuisine_recipes', [], Response::HTTP_SEE_OTHER);
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

    /**
     * @param Recipe $recipe
     * @return Response
     */
    #[Route('/{id}', name: 'recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): Response
    {
        return $this->render('MaCuisine/recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * @param Request $request
     * @param Recipe $recipe
     * @param RecipeFormHandler $recipeFormHandler
     * @param UtensilRepository $utensilRepository
     * @return Response
     */
    #[Route('/{id}/edit', name: 'recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, RecipeFormHandler $recipeFormHandler, UtensilRepository $utensilRepository): Response
    {
        $currentImage = $recipe->getImage();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $recipeFormHandler->handleImage($imageFile, $recipe, $currentImage);
            }

            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute('app_macuisine_recipes', [], Response::HTTP_SEE_OTHER);
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

    /**
     * @param Request $request
     * @param Recipe $recipe
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_macuisine_recipes', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param IngredientRepository $ingredientRepository
     * @return JsonResponse
     */
    #[Route('/ajax/ingredients', name: 'recipe_ingredients_ajax', methods: ['GET'])]
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
