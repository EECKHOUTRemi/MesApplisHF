<?php

namespace App\Controller\MonPoids;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monpoids', name: 'app_monpoids_'), IsGranted('ROLE_USER')]
/** Point d'entrée de la section MonPoids : redirige vers la vue globale. */
final class MonPoidsController extends AbstractController
{
    /** @return Response */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // le template MonPoids/index.html.twig n'existe pas : la vraie page
        // d'accueil de la section est la vue globale
        return $this->redirectToRoute('app_mon_poids_global');
    }
}
