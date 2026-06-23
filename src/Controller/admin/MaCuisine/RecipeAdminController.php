<?php

namespace App\Controller\admin\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Form\MaCuisine\RecipeAdminType;
use App\Repository\MaCuisine\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/macuisine/recipe'),
IsGranted('ROLE_ADMIN')]
/** CRUD admin des recettes MaCuisine (formulaire simplifié sans gestion d'ingrédients JS). */
final class RecipeAdminController extends AbstractController
{
    /**
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route(name: 'app_admin_macuisine_recipe_index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): Response
    {
        return $this->render('admin/MaCuisine/recipe/index.html.twig', [
            'recipes' => $recipeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_macuisine_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeAdminType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recipe);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    /**
     * @param Recipe $recipe
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_macuisine_recipe_show', methods: ['GET'])]
    public function show(Recipe $recipe): Response
    {
        return $this->render('admin/MaCuisine/recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * @param Request $request
     * @param Recipe $recipe
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_admin_macuisine_recipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipeAdminType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Recipe $recipe
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_macuisine_recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_macuisine_recipe_index', [], Response::HTTP_SEE_OTHER);
    }
}
