<?php

namespace App\Controller\admin\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Form\MaCuisine\FavoriteType;
use App\Repository\MaCuisine\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD admin des favoris MaCuisine.
 *
 * L'accès est réservé à ROLE_ADMIN par `access_control` sur `^/admin`
 * (cf. config/packages/security.yaml), sans attribut IsGranted sur la classe.
 */
#[Route('/admin/macuisine/favorite')]
final class FavoriteController extends AbstractController
{
    /**
     * @param FavoriteRepository $favoriteRepository
     * @return Response
     */
    #[Route(name: 'app_admin_ma_cuisine_favorite_index', methods: ['GET'])]
    public function index(FavoriteRepository $favoriteRepository): Response
    {
        return $this->render('admin/MaCuisine/favorite/index.html.twig', [
            'favorites' => $favoriteRepository->findAll(),
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_admin_ma_cuisine_favorite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Pas d'entité pré-construite : Favorite exige son utilisateur et sa recette
        // à la construction, c'est `empty_data` (cf. FavoriteType) qui l'instancie
        // à partir des champs soumis.
        $form = $this->createForm(FavoriteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $favorite = $form->getData();
            $entityManager->persist($favorite);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_ma_cuisine_favorite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/favorite/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @param Favorite $favorite
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_ma_cuisine_favorite_show', methods: ['GET'])]
    public function show(Favorite $favorite): Response
    {
        return $this->render('admin/MaCuisine/favorite/show.html.twig', [
            'favorite' => $favorite,
        ]);
    }

    /**
     * @param Request $request
     * @param Favorite $favorite
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_admin_ma_cuisine_favorite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Favorite $favorite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FavoriteType::class, $favorite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_ma_cuisine_favorite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/favorite/edit.html.twig', [
            'favorite' => $favorite,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Favorite $favorite
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_ma_cuisine_favorite_delete', methods: ['POST'])]
    public function delete(Request $request, Favorite $favorite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $favorite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($favorite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_ma_cuisine_favorite_index', [], Response::HTTP_SEE_OTHER);
    }
}
