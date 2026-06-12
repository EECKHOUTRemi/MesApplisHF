<?php

namespace App\Controller\MonPoids;

use App\Entity\MonPoids\Measurement;
use App\Form\MonPoids\MeasurementType;
use App\Repository\MonPoids\MeasurementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/monpoids/measurement', name: 'app_MonPoids_measurement_'),
IsGranted('ROLE_USER')]
final class MeasurementController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(MeasurementRepository $measurementRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $measurements = $measurementRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'ASC']);
        $dates = array_map(
            fn (Measurement $measurement) => $measurement->getCreatedAt()->format('d/m/Y'),
            $measurements,
        );
        $chestData = array_map(
            fn (Measurement $measurement) => $measurement->getChest(),
            $measurements,
        );
        $hipsData = array_map(
            fn (Measurement $measurement) => $measurement->getHips(),
            $measurements,
        );
        $thighData = array_map(
            fn (Measurement $measurement) => $measurement->getThigh(),
            $measurements,
        );
        $waistData = array_map(
            fn (Measurement $measurement) => $measurement->getWaist(),
            $measurements,
        );

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Poitrine',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $chestData,
                ],
                [
                    'label' => 'Hanches',
                    'backgroundColor' => 'rgb(54, 162, 235)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'data' => $hipsData,
                ],
                [
                    'label' => 'Cuisse',
                    'backgroundColor' => 'rgb(255, 205, 86)',
                    'borderColor' => 'rgb(255, 205, 86)',
                    'data' => $thighData,
                ],
                [
                    'label' => 'Taille',
                    'backgroundColor' => 'rgb(75, 192, 192)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'data' => $waistData,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'spanGaps' => true,
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);
        return $this->render('MonPoids/measurement/index.html.twig', [
            'measurements' => $measurements,
            'chart' => $chart,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $measurement = new Measurement();
        $form = $this->createForm(MeasurementType::class, $measurement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $measurement->setCreatedAt($measurement->getCreatedAt() ?? new \DateTimeImmutable());
            $measurement->setUser($this->getUser());
            $entityManager->persist($measurement);
            $entityManager->flush();

            return $this->redirectToRoute('app_MonPoids_measurement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MonPoids/measurement/new.html.twig', [
            'measurement' => $measurement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Measurement $measurement): Response
    {
        return $this->render('MonPoids/measurement/show.html.twig', [
            'measurement' => $measurement,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Measurement $measurement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MeasurementType::class, $measurement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_MonPoids_measurement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MonPoids/measurement/edit.html.twig', [
            'measurement' => $measurement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Measurement $measurement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$measurement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($measurement);
            $entityManager->flush();
        }

        if ($request->query->get('from') === 'admin') {
            return $this->redirectToRoute('app_admin_monpoids_measurement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_MonPoids_measurement_index', [], Response::HTTP_SEE_OTHER);
    }
}
