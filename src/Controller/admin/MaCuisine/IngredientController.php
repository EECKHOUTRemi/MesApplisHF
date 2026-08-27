<?php

namespace App\Controller\admin\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Form\MaCuisine\IngredientType;
use App\Repository\MaCuisine\IngredientRepository;
use App\Repository\MaCuisine\RefRecipeIngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/macuisine/ingredient', name:'app_admin_macuisine_ingredient_')]
/** CRUD admin des ingrédients MaCuisine. */
final class IngredientController extends AbstractController
{
    /**
     * @param IngredientRepository $ingredientRepository
     * @return Response
     */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(IngredientRepository $ingredientRepository): Response
    {
        return $this->render('admin/MaCuisine/ingredient/index.html.twig', [
            'ingredients' => $ingredientRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ingredient = new Ingredient();
        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ingredient);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_ingredient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/ingredient/new.html.twig', [
            'ingredient' => $ingredient,
            'form' => $form,
        ]);
    }

    /**
     * @param Ingredient $ingredient
     * @return Response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Ingredient $ingredient): Response
    {
        return $this->render('admin/MaCuisine/ingredient/show.html.twig', [
            'ingredient' => $ingredient,
        ]);
    }

    /**
     * @param Request $request
     * @param Ingredient $ingredient
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ingredient $ingredient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_ingredient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/ingredient/edit.html.twig', [
            'ingredient' => $ingredient,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Ingredient $ingredient
     * @param EntityManagerInterface $entityManager
     * @param RefRecipeIngredientRepository $refRecipeIngredientRepository
     * @return Response
     */
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Ingredient $ingredient,
        EntityManagerInterface $entityManager,
        RefRecipeIngredientRepository $refRecipeIngredientRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $ingredient->getId(), $request->getPayload()->getString('_token'))) {
            if ($refRecipeIngredientRepository->count(['ingredient' => $ingredient]) > 0) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        'Impossible de supprimer « %s » : cet ingrédient est utilisé dans au moins une recette.',
                        $ingredient->getName()
                    )
                );
            } else {
                $entityManager->remove($ingredient);
                $entityManager->flush();

                $this->addFlash('success', 'Ingrédient supprimé.');
            }
        }

        return $this->redirectToRoute('app_admin_macuisine_ingredient_index', [], Response::HTTP_SEE_OTHER);
    }
}
