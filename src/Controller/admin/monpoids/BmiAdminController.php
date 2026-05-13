<?php

namespace App\Controller\admin\monpoids;

use App\Repository\monpoids\BmiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/monpoids/bmi', name: 'app_admin_monpoids_bmi_'), IsGranted('ROLE_ADMIN')]
final class BmiAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BmiRepository $bmiRepository): Response
    {
        return $this->render('admin/monpoids/bmi/index.html.twig', [
            'bmis' => $bmiRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
