<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil', name: 'app_profil_'), IsGranted('ROLE_USER')]
/** Affiche la page de profil public de l'utilisateur connecté. */
final class ProfilController extends AbstractController
{
    /** @return Response */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig', [
            'profil' => $this->getUser(),
        ]);
    }
}
