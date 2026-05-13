<?php

namespace App\Controller\admin\monpoids;

use App\Repository\monpoids\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/monpoids/measurement', name: 'app_admin_monpoids_measurement_'), IsGranted('ROLE_ADMIN')]
final class MeasurementAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(MeasurementRepository $measurementRepository): Response
    {
        return $this->render('admin/monpoids/measurement/index.html.twig', [
            'measurements' => $measurementRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
