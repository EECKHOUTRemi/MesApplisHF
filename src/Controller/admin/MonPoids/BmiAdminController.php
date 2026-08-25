<?php

namespace App\Controller\admin\MonPoids;

use App\Repository\MonPoids\BmiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/monpoids/bmi', name: 'app_admin_monpoids_bmi_')]
/** Vue admin en lecture seule des enregistrements IMC de tous les utilisateurs. */
final class BmiAdminController extends AbstractController
{
    /**
     * @param BmiRepository $bmiRepository
     * @return Response
     */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(BmiRepository $bmiRepository): Response
    {
        return $this->render('admin/MonPoids/bmi/index.html.twig', [
            'bmis' => $bmiRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
