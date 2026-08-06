<?php

namespace App\Controller\MonPoids;

use App\Entity\MonPoids\Bmi;
use App\Form\MonPoids\BmiType;
use App\Repository\MonPoids\BmiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/monpoids/bmi', name: 'app_MonPoids_bmi_'),
IsGranted('ROLE_USER')]
/**
 * CRUD des enregistrements IMC MonPoids côté utilisateur.
 * L'index génère deux graphiques Chart.js (évolution IMC et poids) pour l'utilisateur connecté.
 * Les actions show/edit/delete vérifient que l'entrée appartient bien à l'utilisateur courant.
 */
final class BmiController extends AbstractController
{
    /**
     * @param BmiRepository $bmiRepository
     * @param ChartBuilderInterface $chartBuilder
     * @return Response
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BmiRepository $bmiRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $bmis = $bmiRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'ASC']);
        $dates = array_map(
            function (Bmi $bmi) {
                return $bmi->getCreatedAt()->format('d/m/Y');
            },
            $bmis,
        );
        $bmiData = array_map(
            function (Bmi $bmi) {
                return $bmi->getBmi();
            },
            $bmis,
        );
        $weightData = array_map(
            function (Bmi $bmi) {
                return $bmi->getWeight();
            },
            $bmis,
        );
        $count = count($dates);
        $minLine = array_fill(0, $count, 19);
        $maxLine = array_fill(0, $count, 25);

        $bmiChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $green = 'rgba(75, 192, 75, 0.5)';
        $bmiChart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'IMC',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $bmiData,
                ],
                [
                    'label' => 'IMC normal',
                    'backgroundColor' => $green,
                    'borderColor' => $green,
                    'data' => $minLine,
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
                [
                    'label' => '__hidden__',
                    'backgroundColor' => $green,
                    'borderColor' => $green,
                    'data' => $maxLine,
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
        ]);
        $bmiChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'suggestedMin' => 15,
                    'suggestedMax' => 35,
                ],
            ],
        ]);

        $weightChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $weightChart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Poids',
                    'backgroundColor' => 'rgb(54, 162, 235)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'data' => $weightData,
                ],
            ],
        ]);
        $weightChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        return $this->render('MonPoids/bmi/index.html.twig', [
            'bmis' => $bmis,
            'bmiChart' => $bmiChart,
            'weightChart' => $weightChart,
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepo
     * @return Response
     */
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
            $bmi->setCreatedAt($bmi->getCreatedAt() ?? new \DateTimeImmutable());
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

    /**
     * @param Bmi $bmi
     * @return Response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bmi $bmi): Response
    {
        if ($bmi->getUser()->getUserIdentifier() !== $this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('app_MonPoids_bmi_index');
        }

        return $this->render('MonPoids/bmi/show.html.twig', [
            'bmi' => $bmi,
        ]);
    }

    /**
     * @param Request $request
     * @param Bmi $bmi
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bmi $bmi, EntityManagerInterface $entityManager): Response
    {
        if ($bmi->getUser()->getUserIdentifier() !== $this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('app_MonPoids_bmi_index');
        }

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

    /**
     * @param Request $request
     * @param Bmi $bmi
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bmi $bmi, EntityManagerInterface $entityManager): Response
    {
        if ($bmi->getUser()->getUserIdentifier() !== $this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('app_MonPoids_bmi_index');
        }

        if ($this->isCsrfTokenValid('delete' . $bmi->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($bmi);
            $entityManager->flush();
        }

        if ($request->query->get('from') === 'admin') {
            return $this->redirectToRoute('app_admin_monpoids_bmi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_MonPoids_bmi_index', [], Response::HTTP_SEE_OTHER);
    }
}
