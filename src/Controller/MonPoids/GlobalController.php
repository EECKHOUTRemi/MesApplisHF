<?php

namespace App\Controller\MonPoids;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Vue de synthèse MonPoids : résumé agrégé IMC + mensurations. */
final class GlobalController extends AbstractController
{
    /** @return Response */
    #[Route('/monpoids/global', name: 'app_mon_poids_global'),
    IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('MonPoids/global/index.html.twig', [
            'controller_name' => 'GlobalController',
        ]);
    }
}
