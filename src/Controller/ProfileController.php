<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'app_profil_'), IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $profile = [
            'firstName' => 'Rémi',
            'lastName' => 'Eeckhout',
            'username' => 'remi_hf',
            'email' => 'remieeckhout9@gmail.com',
            'bio' => 'Passionné de cuisine et de sport. J\'aime tester de nouvelles recettes et suivre mes progrès au quotidien.',
            'memberSince' => new \DateTimeImmutable('2025-09-12'),
            'location' => 'Lille, France',
            'stats' => [
                'recipes' => 27,
                'followers' => 142,
                'following' => 86,
                'weighIns' => 53,
            ],
        ];

        return $this->render('profile/index.html.twig', [
            'profile' => $profile,
        ]);
    }
}
