<?php

namespace App\Controller\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Form\MaCuisine\RecipeType;
use App\Handler\ImageHandler;
use App\Handler\RecipeFormHandler;
use App\Repository\MaCuisine\CategoryRepository;
use App\Repository\MaCuisine\FavoriteRepository;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RecipeRepository;
use App\Repository\MaCuisine\UtensilRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
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
     * Options communes du fil (filtres catégories + ustensiles, favoris de l'utilisateur)
     * injectées dans le template.
     *
     * @param CategoryRepository $categoryRepository
     * @param UtensilRepository $utensilRepository
     * @param FavoriteRepository $favoriteRepository
     * @param Recipe[] $recipes
     * @return array<string, mixed>
     */
    private function feedFilterOptions(
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

    /**
     * Tableau de bord MaCuisine : recettes récentes et compteurs globaux.
     *
     * @param RecipeRepository $recipeRepository
     * @param IngredientRepository $ingredientRepository
     * @return Response
     */
    #[Route(name: 'index')]
    public function dashboard(RecipeRepository $recipeRepository, IngredientRepository $ingredientRepository): Response
    {
        $recentRecipes = $recipeRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        return $this->render('MaCuisine/index.html.twig', [
            'recentRecipes' => $recentRecipes,
            'totalRecipes' => $recipeRepository->count([]),
            'totalIngredients' => $ingredientRepository->count([]),
            'myRecipesCount' => $recipeRepository->count(['author' => $this->getUser()]),
        ]);
    }

    /**
     * @param Request $request
     * @param RecipeRepository $recipeRepository
     * @param CategoryRepository $categoryRepository
     * @param UtensilRepository $utensilRepository
     * @param FavoriteRepository $favoriteRepository
     * @return Response
     */
    #[Route('/feed', name: 'feed', methods: ['GET'])]
    public function index(
        Request $request,
        RecipeRepository $recipeRepository,
        CategoryRepository $categoryRepository,
        UtensilRepository $utensilRepository,
        FavoriteRepository $favoriteRepository
    ): Response {
        $query = $request->query->get('q');
        $ingredients = $request->query->all('ingredients');
        $utensils = $request->query->all('utensils');
        $categories = $request->query->all('categories');
        $difficulty = $request->query->get('difficulty');
        $cost = $request->query->get('cost');
        $time = $request->query->get('time');

        $difficulty = ($difficulty !== null && $difficulty !== '') ? (int) $difficulty : null;
        $cost = ($cost !== null && $cost !== '') ? (int) $cost : null;
        $time = ($time !== null && $time !== '') ? (int) $time : null;

        $hasFilters = ($query !== null && $query !== '')
            || $ingredients !== []
            || $utensils !== []
            || $categories !== []
            || $difficulty !== null
            || $cost !== null
            || $time !== null;

        if ($hasFilters) {
            $recipes = $recipeRepository->findWithQuery(
                $query,
                $ingredients,
                $utensils,
                $categories,
                $difficulty,
                $cost,
                $time
            );
        } else {
            $recipes = $recipeRepository->findAll();
        }

        return $this->render('MaCuisine/recipe/feed.html.twig', [
            'recipes' => $recipes,
            'mine' => false,
        ] + $this->feedFilterOptions($categoryRepository, $utensilRepository, $favoriteRepository, $recipes));
    }

    /**
     * @param RecipeRepository $recipeRepository
     * @param CategoryRepository $categoryRepository
     * @param UtensilRepository $utensilRepository
     * @param FavoriteRepository $favoriteRepository
     * @return Response
     */
    #[Route('/mine', name:'mine')]
    public function mine(
        RecipeRepository $recipeRepository,
        CategoryRepository $categoryRepository,
        UtensilRepository $utensilRepository,
        FavoriteRepository $favoriteRepository
    ): Response {
        $recipes = $recipeRepository->findBy(['author' => $this->getUser()]);

        return $this->render('MaCuisine/recipe/feed.html.twig', [
            'recipes' => $recipes,
            'mine' => true,
        ] + $this->feedFilterOptions($categoryRepository, $utensilRepository, $favoriteRepository, $recipes));
    }

    /**
     * @param Request $request
     * @param RecipeFormHandler $recipeFormHandler
     * @param UtensilRepository $utensilRepository
     * @return Response
     */
    #[Route('/new', name: 'recipe_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        RecipeFormHandler $recipeFormHandler,
        UtensilRepository $utensilRepository
    ): Response {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $recipeFormHandler->addImage($imageFile, $recipe);
            }

            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute('app_macuisine_index', [], Response::HTTP_SEE_OTHER);
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
     * @param FavoriteRepository $favoriteRepository
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/{id}', name: 'recipe_show', methods: ['GET'])]
    public function show(
        Recipe $recipe,
        FavoriteRepository $favoriteRepository,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $isFavorite = $favoriteRepository->isFavorite($recipe, $user);

        return $this->render('MaCuisine/recipe/show.html.twig', [
            'recipe' => $recipe,
            'isFavorite' => $isFavorite,
            'favoriteCount' => $favoriteRepository->count(['recipe' => $recipe]),
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
    public function edit(
        Request $request,
        Recipe $recipe,
        RecipeFormHandler $recipeFormHandler,
        UtensilRepository $utensilRepository
    ): Response {
        $currentImage = $recipe->getImage();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $recipeFormHandler->addImage($imageFile, $recipe, $currentImage);
            }

            $submittedData = $request->request->all();
            $recipeFormHandler->persistAndFlush($recipe, $submittedData);

            return $this->redirectToRoute(
                'app_macuisine_recipe_show',
                ['id' => $recipe->getId()],
                Response::HTTP_SEE_OTHER
            );
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
     * @param ImageHandler $imgHandler
     * @return Response
     */
    #[Route('/{id}', name: 'recipe_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Recipe $recipe,
        EntityManagerInterface $entityManager,
        ImageHandler $imgHandler
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $recipe->getId(), $request->getPayload()->getString('_token'))) {
            $filename = $recipe->getImage();
            if ($filename) {
                try {
                    $imgHandler->removeRecipeImage($filename);
                } catch (FileNotFoundException  $th) {
                    $filename = null;
                }
            }

            $entityManager->remove($recipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_macuisine_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param IngredientRepository $ingredientRepository
     * @return JsonResponse
     */
    #[Route('/ajax/ingredients', name: 'recipe_ingredients_ajax', methods: ['GET'])]
    public function ingredientsAjax(
        Request $request,
        IngredientRepository $ingredientRepository
    ): JsonResponse {
        $term = $request->query->get('term');

        if ($term === null) {
            $rawIngredients = $ingredientRepository->findAll();
        } else {
            $rawIngredients = $ingredientRepository->findNameLike($term);
        }
        $handledIngredients = [];
        foreach ($rawIngredients as $ingredient) {
            $handledIngredients[] = [
                'id' => $ingredient->getId(),
                'name' => $ingredient->getName(),
            ];
        }

        return $this->json($handledIngredients);
    }
}
