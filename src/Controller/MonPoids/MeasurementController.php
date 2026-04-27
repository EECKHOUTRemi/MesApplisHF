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

#[Route('/monpoids/measurement', name: 'app_MonPoids_measurement_')]
final class MeasurementController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(MeasurementRepository $measurementRepository): Response
    {
        return $this->render('MonPoids/measurement/index.html.twig', [
            'measurements' => $measurementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $measurement = new Measurement();
        $form = $this->createForm(MeasurementType::class, $measurement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $measurement->setCreatedAt(new \DateTimeImmutable());
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

        return $this->redirectToRoute('app_MonPoids_measurement_index', [], Response::HTTP_SEE_OTHER);
    }
}
