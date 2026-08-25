<?php

namespace App\Controller\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Repository\MaCuisine\FavoriteRepository;
use App\Repository\MaCuisine\RecipeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/macuisine/favorite', name:'app_macuisine_favorite_'),
IsGranted('ROLE_USER')]
final class FavoriteController extends AbstractController
{
    #[Route('/index', name: 'index')]
    public function index(FavoriteRepository $favoriteRepository): Response
    {
        $favorites = $favoriteRepository->findAllForConnectedUser();

        return $this->render('MaCuisine/favorite/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    #[Route("/toggleFavorite", name: 'toggle', methods: ['POST'])]
    public function toggleFavorite(
        Request $request,
        RecipeRepository $recipeRepository,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $recipeId = $request->request->get('recipeId');
        $recipe = $recipeRepository->find($recipeId);

        if (!$recipe) {
            throw $this->createNotFoundException();
        }

        $favorite = $favoriteRepository->findOneBy(['recipe' => $recipe, 'user' => $this->getUser()]);

        if ($favorite) {
            $em->remove($favorite);
            $message = 'Recette retirée de vos favoris.';
        } else {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $favorite = new Favorite($user, $recipe);
            $em->persist($favorite);
            $message = 'Recette ajoutée à vos favoris !';
        }

        $em->flush();

        $this->addFlash('success', $message);

        // Retour à la page d'où vient le clic (fil, mes recettes, favoris…),
        // avec la fiche recette en repli si le referer est absent.
        $referer = $request->headers->get('referer');

        return $referer !== null
            ? $this->redirect($referer)
            : $this->redirectToRoute('app_macuisine_recipe_show', ['id' => $recipe->getId()]);
    }
}
