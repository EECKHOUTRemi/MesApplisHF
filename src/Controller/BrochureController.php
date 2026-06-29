<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages vitrine publiques présentant MesApplisHF et ses deux outils (MaCuisine, MonPoids).
 * Accessibles sans authentification : point d'entrée pour les visiteurs avant inscription.
 */
#[Route('/', name: 'app_brochure_', methods: ['GET'])]
final class BrochureController extends AbstractController
{
    /** @return Response */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brochure/index.html.twig');
    }

    /** @return Response */
    #[Route('macuisine', name: 'macuisine', methods: ['GET'])]
    public function maCuisine(): Response
    {
        return $this->render('brochure/macuisine.html.twig');
    }

    /** @return Response */
    #[Route('monpoids', name: 'monpoids', methods: ['GET'])]
    public function monPoids(): Response
    {
        return $this->render('brochure/monpoids.html.twig');
    }
}
