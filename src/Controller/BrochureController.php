<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages vitrine publiques présentant MesApplisHF et ses deux outils (MaCuisine, MonPoids).
 * Accessibles sans authentification : point d'entrée pour les visiteurs avant inscription.
 */
final class BrochureController extends AbstractController
{
    /** @return Response */
    #[Route('/', name: 'app_root', methods: ['GET'])]
    public function root(): Response
    {
        // Connecté : tableau de bord ; sinon : page vitrine publique.
        return $this->redirectToRoute($this->isGranted('ROLE_USER') ? 'app_index' : 'app_brochure_index');
    }

    /** @return Response */
    #[Route('/discover', name: 'app_brochure_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brochure/index.html.twig');
    }

    /** @return Response */
    #[Route('/discover/macuisine', name: 'app_brochure_macuisine', methods: ['GET'])]
    public function maCuisine(): Response
    {
        return $this->render('brochure/macuisine.html.twig');
    }

    /** @return Response */
    #[Route('/discover/monpoids', name: 'app_brochure_monpoids', methods: ['GET'])]
    public function monPoids(): Response
    {
        return $this->render('brochure/monpoids.html.twig');
    }
}
