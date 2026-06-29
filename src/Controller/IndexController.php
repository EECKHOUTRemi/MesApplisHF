<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Page d'accueil principale de l'application (tableau de bord). */
final class IndexController extends AbstractController
{
    /**
     * @return Response
     */
    #[Route('/home', name: 'app_index'), IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
        ]);
    }
}
