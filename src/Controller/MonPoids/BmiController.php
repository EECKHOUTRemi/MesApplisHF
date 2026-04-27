<?php

namespace App\Controller\MonPoids;

use App\Entity\MonPoids\Bmi;
use App\Form\MonPoids\BmiType;
use App\Repository\MonPoids\BmiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/monpoids/bmi', name: 'app_MonPoids_bmi_')]
final class BmiController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BmiRepository $bmiRepository): Response
    {
        return $this->render('MonPoids/bmi/index.html.twig', [
            'bmis' => $bmiRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bmi = new Bmi();
        $form = $this->createForm(BmiType::class, $bmi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($bmi);
            $entityManager->flush();

            return $this->redirectToRoute('app_MonPoids_bmi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MonPoids/bmi/new.html.twig', [
            'bmi' => $bmi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bmi $bmi): Response
    {
        return $this->render('MonPoids/bmi/show.html.twig', [
            'bmi' => $bmi,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bmi $bmi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BmiType::class, $bmi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_MonPoids_bmi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('MonPoids/bmi/edit.html.twig', [
            'bmi' => $bmi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bmi $bmi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bmi->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($bmi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_MonPoids_bmi_index', [], Response::HTTP_SEE_OTHER);
    }
}
