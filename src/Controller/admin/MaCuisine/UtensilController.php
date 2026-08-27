<?php

namespace App\Controller\admin\MaCuisine;

use App\Entity\MaCuisine\Utensil;
use App\Form\MaCuisine\UtensilType;
use App\Repository\MaCuisine\UtensilRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/macuisine/utensil', name: 'app_admin_macuisine_utensil_')]
/** CRUD admin des ustensiles MaCuisine. */
final class UtensilController extends AbstractController
{
    /**
     * @param UtensilRepository $utensilRepository
     * @return Response
     */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(UtensilRepository $utensilRepository): Response
    {
        return $this->render('admin/MaCuisine/utensil/index.html.twig', [
            'utensils' => $utensilRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utensil = new Utensil();
        $form = $this->createForm(UtensilType::class, $utensil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utensil->setCreatedAt(new DateTimeImmutable());
            $utensil->setCreatedBy($this->getUser());
            $entityManager->persist($utensil);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_utensil_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/utensil/new.html.twig', [
            'utensil' => $utensil,
            'form' => $form,
        ]);
    }

    /**
     * @param Utensil $utensil
     * @return Response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Utensil $utensil): Response
    {
        return $this->render('admin/MaCuisine/utensil/show.html.twig', [
            'utensil' => $utensil,
        ]);
    }

    /**
     * @param Request $request
     * @param Utensil $utensil
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utensil $utensil, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtensilType::class, $utensil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utensil->setUpdatedAt(new DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_macuisine_utensil_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/MaCuisine/utensil/edit.html.twig', [
            'utensil' => $utensil,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Utensil $utensil
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Utensil $utensil, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $utensil->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utensil);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_macuisine_utensil_index', [], Response::HTTP_SEE_OTHER);
    }
}
