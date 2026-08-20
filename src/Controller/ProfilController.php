<?php

namespace App\Controller;

use App\Repository\Friends\RelationshipRepository;
use App\Repository\MaCuisine\RecipeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil', name: 'app_profil_'), IsGranted('ROLE_USER')]
/** Affiche la page de profil public de l'utilisateur connecté et des autres membres. */
final class ProfilController extends AbstractController
{
    /** @return Response */
    #[Route(name: 'index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig', [
            'profil' => $this->getUser(),
        ]);
    }

    /**
     * @param int $id
     * @param UserRepository $userRepository
     * @param RelationshipRepository $relationshipRepository
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/{id}', name: 'seeOtherUser', methods: ['GET'], requirements: ['id' => '[0-9]+'])]
    public function seeOtherUser(
        int $id,
        UserRepository $userRepository,
        RelationshipRepository $relationshipRepository,
        RecipeRepository $recipeRepository,
    ): Response {
        $other = $userRepository->find($id);

        if ($other === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Le template bascule sur l'UI « mon profil » (dont la modale photo) dès que le profil
        // consulté est celui de l'utilisateur connecté, or seul index() fournit `pictureForm`.
        // On redirige donc vers l'URL canonique plutôt que de rendre une page incomplète.
        if ($other->getId() === $currentUser->getId()) {
            return $this->redirectToRoute('app_profil_index');
        }

        $relationship   = $relationshipRepository->findRelationShipByUsers($currentUser, $other);

        return $this->render('profil/index.html.twig', [
            'profil'         => $other,
            'relationStatus' => $relationship?->getStatus(),
            'relationUpdatedAt' => $relationship?->getUpdatedAt()?->format('d/m/Y'),
            // Déjà visibles de tous dans le fil MaCuisine : rien de nouveau n'est exposé.
            'recipes'        => $recipeRepository->findBy(['author' => $other], ['createdAt' => 'DESC']),
        ]);
    }
}
