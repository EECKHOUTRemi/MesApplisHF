<?php

namespace App\Controller\admin\MonPoids;

use App\Repository\MonPoids\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/monpoids/measurement', name: 'app_admin_monpoids_measurement_'), IsGranted('ROLE_ADMIN')]
/** Vue admin en lecture seule des mensurations de tous les utilisateurs. */
final class MeasurementAdminController extends AbstractController
{
    /**
     * @param MeasurementRepository $measurementRepository
     * @return Response
     */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(MeasurementRepository $measurementRepository): Response
    {
        return $this->render('admin/MonPoids/measurement/index.html.twig', [
            'measurements' => $measurementRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
