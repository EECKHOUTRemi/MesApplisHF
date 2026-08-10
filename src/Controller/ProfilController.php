<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilePictureType;
use App\Repository\RelationshipRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil', name: 'app_profil_'), IsGranted('ROLE_USER')]
/** Affiche la page de profil public de l'utilisateur connecté et gère sa photo de profil. */
final class ProfilController extends AbstractController
{
    /**
     * @param string $profileImagesDirectory Chemin absolu du dossier de stockage des photos de profil
     */
    public function __construct(
        #[Autowire(param: 'profile_images_directory')] private readonly string $profileImagesDirectory,
    ) {
    }

    /** @return Response */
    #[Route(name: 'index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig', [
            'profil' => $this->getUser(),
            'pictureForm' => $this->createForm(ProfilePictureType::class),
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/photo', name: 'picture', methods: ['POST'])]
    public function updatePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfilePictureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            $filesystem = new Filesystem();
            $filesystem->mkdir($this->profileImagesDirectory);

            if ($user->getImage() !== null) {
                $filesystem->remove($this->profileImagesDirectory . '/' . $user->getImage());
            }

            $extension = strtolower((string) ($imageFile->guessExtension() ?: $imageFile->getClientOriginalExtension() ?: 'bin'));
            $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
            $imageFile->move($this->profileImagesDirectory, $newFilename);
            $user->setImage($newFilename);
            $entityManager->flush();

            $this->addFlash('success', 'Photo de profil mise à jour.');
        } else {
            $this->addFlash('danger', 'La photo de profil n\'a pas pu être mise à jour.');
        }

        return $this->redirectToRoute('app_profil_index');
    }

    /**
     * @param int $id
     * @param UserRepository $userRepository
     * @param RelationshipRepository $relationshipRepository
     * @return Response
     */
    #[Route('/{id}', name: 'seeOtherUser', methods: ['GET'], requirements: ['id' => '[0-9]+'])]
    public function seeOtherUser(int $id, UserRepository $userRepository, RelationshipRepository $relationshipRepository): Response
    {
        $other = $userRepository->find($id);

        if ($other === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $relationship   = $relationshipRepository->findRelationShipByUsers($this->getUser(), $other);

        return $this->render('profil/index.html.twig', [
            'profil'         => $other,
            'relationStatus' => $relationship?->getStatus(),
            'relationUpdatedAt' => $relationship?->getUpdatedAt()->format('d/m/Y')
        ]);
    }
}
