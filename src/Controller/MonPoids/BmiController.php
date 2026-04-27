<?php

namespace App\Controller\MonPoids;

use App\Entity\MonPoids\Bmi;
use App\Entity\User;
use App\Form\MonPoids\BmiType;
use App\Repository\MonPoids\BmiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/monpoids/bmi', name: 'app_MonPoids_bmi_')]
final class BmiController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BmiRepository $bmiRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $bmis = $bmiRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'ASC']);
        $dates = array_map(
            fn (Bmi $bmi) => $bmi->getCreatedAt()->format('d/m/Y'),
            $bmis,
        );
        $bmiData = array_map(
            fn (Bmi $bmi) => $bmi->getBmi(),
            $bmis,
        );
        $weightData = array_map(
            fn (Bmi $bmi) => $bmi->getWeight(),
            $bmis,
        );
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'IMC',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $bmiData,
                ],
                [
                    'label' => 'Poids',
                    'backgroundColor' => 'rgb(54, 162, 235)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'data' => $weightData,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);
        
        return $this->render('MonPoids/bmi/index.html.twig', [
            'bmis' => $bmis,
            'chart' => $chart,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($this->getUser());
        $userHeight = $user->getHeight();
        $bmi = new Bmi();
        if ($userHeight) {
            $bmi->setHeight($userHeight);
        }
        $form = $this->createForm(BmiType::class, $bmi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$userHeight) {
                $user->setHeight($bmi->getHeight());
                $entityManager->persist($user);
            }
            $bmi->setCreatedAt(new \DateTimeImmutable());
            $bmi->setUser($this->getUser());
            $bmi->setBmi($bmi->getHeight(), $bmi->getWeight());
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
            $bmi->setBmi($bmi->getHeight(), $bmi->getWeight());
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
