<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/macuisine', name: 'app_macuisine_')]
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
