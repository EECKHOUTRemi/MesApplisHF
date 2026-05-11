<?php

namespace App\Controller\monpoids;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GlobalController extends AbstractController
{
    #[Route('/monpoids/global', name: 'app_mon_poids_global'),
    IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('monpoids/global/index.html.twig', [
            'controller_name' => 'GlobalController',
        ]);
    }
}
