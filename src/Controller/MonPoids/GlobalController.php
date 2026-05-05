<?php

namespace App\Controller\MonPoids;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GlobalController extends AbstractController
{
    #[Route('/monpoids/global', name: 'app_mon_poids_global')]
    public function index(): Response
    {
        return $this->render('MonPoids/global/index.html.twig', [
            'controller_name' => 'GlobalController',
        ]);
    }
}
