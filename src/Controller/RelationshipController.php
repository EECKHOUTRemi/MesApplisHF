<?php

namespace App\Controller;

use App\Entity\Relationship;
use App\Form\RelationshipType;
use App\Repository\RelationshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/relationship'), IsGranted('ROLE_ADMIN')]
final class RelationshipController extends AbstractController
{
    #[Route(name: 'app_relationship_index', methods: ['GET'])]
    public function index(RelationshipRepository $relationshipRepository): Response
    {
        return $this->render('relationship/index.html.twig', [
            'relationships' => $relationshipRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_relationship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $relationship = new Relationship();
        $form = $this->createForm(RelationshipType::class, $relationship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($relationship);
            $entityManager->flush();

            return $this->redirectToRoute('app_relationship_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('relationship/new.html.twig', [
            'relationship' => $relationship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_relationship_show', methods: ['GET'])]
    public function show(Relationship $relationship): Response
    {
        return $this->render('relationship/show.html.twig', [
            'relationship' => $relationship,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_relationship_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Relationship $relationship, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RelationshipType::class, $relationship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_relationship_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('relationship/edit.html.twig', [
            'relationship' => $relationship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_relationship_delete', methods: ['POST'])]
    public function delete(Request $request, Relationship $relationship, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$relationship->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($relationship);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_relationship_index', [], Response::HTTP_SEE_OTHER);
    }
}
