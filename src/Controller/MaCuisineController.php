<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/macuisine', name: 'app_macuisine_'), IsGranted('ROLE_USER')]
final class MaCuisineController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index()
    {
        return $this->render("MaCuisine/index.html.twig", [
            'controller_name' => 'CookingRecipesController'
        ]);
    }
}
