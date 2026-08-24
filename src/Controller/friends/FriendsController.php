<?php

namespace App\Controller\friends;

use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Repository\Friends\RelationshipRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use ValueError;

#[Route('/friends', name: 'app_friends_'), IsGranted('ROLE_USER')]
final class FriendsController extends AbstractController
{
    private const ID_REQUIREMENT = '[0-9]+';

    /**
     * @param RelationshipRepository $relationshipRepository
     * @return Response
     */
    #[Route(name: 'index')]
    public function index(RelationshipRepository $relationshipRepository): Response
    {
        $user = $this->getUser();

        return $this->render('friends/index.html.twig', [
            'pendingRequests' => $relationshipRepository->findPendingFor($user),
            'friends'         => $relationshipRepository->findFriendsFor($user),
        ]);
    }

    /**
     * @param int $id
     * @param User $sender
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/sendRequest/{id}', name: 'sendRequest', methods: ['POST'], requirements: ['id' => self::ID_REQUIREMENT])]
    public function sendRequest(
        int $id,
        #[CurrentUser] User $sender,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        $reciever = $userRepository->find($id);

        if ($reciever === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $relationship = new Relationship();
        $relationship->setuser1($sender)
            ->setuser2($reciever)
            ->setStatus(Relationship::STATUS_PENDING)
            ->setCreatedAt(new DateTimeImmutable())
        ;

        $em->persist($relationship);
        $em->flush();

        return $this->redirectToRoute('app_profil_seeOtherUser', ['id' => $id]);
    }

    /**
     * @param int $user2Id
     * @param Request $request
     * @param RelationshipRepository $relationshipRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(
        '/removeRelationship/{user2Id}',
        name: 'removeRelationship',
        methods: ['POST'],
        requirements: ['user2Id' => self::ID_REQUIREMENT]
    )]
    public function removeRelationship(
        int $user2Id,
        Request $request,
        RelationshipRepository $relationshipRepository,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user1 */
        $user1 = $this->getUser();

        $relationship = $relationshipRepository->findOneBy(['user1' => $user1, 'user2' => $user2Id])
            ?? $relationshipRepository->findOneBy(['user1' => $user2Id, 'user2' => $user1]);

        if ($relationship === null) {
            throw $this->createNotFoundException('Relation introuvable.');
        }

        $em->remove($relationship);
        $em->flush();

        if ($request->request->getString('redirect') === 'friends') {
            return $this->redirectToRoute('app_friends_index');
        }

        return $this->redirectToRoute('app_profil_seeOtherUser', ['id' => $user2Id]);
    }

    #[Route(
        '/answerRequest/{id}',
        name: 'answerRequest',
        methods: ['POST'],
        requirements: ['id' => self::ID_REQUIREMENT]
    )]
    public function answerRequest(
        int $id,
        Request $request,
        RelationshipRepository $relationshipRepository,
        EntityManagerInterface $em
    ): Response {
        $answer = $request->request->get('answer');

        if (!in_array($answer, Relationship::STATUSES)) {
            throw new ValueError("Non valid status");
        }

        $relationship = $relationshipRepository->find($id);
        $relationship->setStatus($answer)
            ->setUpdatedAt(new DateTimeImmutable())
        ;

        $em->persist($relationship);
        $em->flush();

        return $this->redirectToRoute('app_friends_index');
    }

    /**
     * @param Request $request
     * @param UserRepository $userRepository
     * @param Packages $packages
     * @param CacheManager $imagineCacheManager
     * @param string $projectDir
     * @return JsonResponse
     */
    #[Route('/users-ajax', name: 'getUsersAjax', methods: ['GET'])]
    public function getUsersAjax(
        Request $request,
        UserRepository $userRepository,
        Packages $packages,
        CacheManager $imagineCacheManager,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): JsonResponse {
        $query = $request->query->getString('q');

        if (mb_strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->searchByUsername($query, $this->getUser());

        return $this->json(
            array_map(
                function (User $user) use ($packages, $imagineCacheManager, $projectDir): array {
                    $path    = $user->getImage() !== null ? 'uploads/pfp/' . $user->getImage() : null;
                    $onDisk  = $path !== null && file_exists($projectDir . '/public/' . $path);
                    $sources = [];

                    if ($onDisk) {
                        $assetUrl = $packages->getUrl($path);

                        foreach (['avif', 'webp', 'png', 'jpeg'] as $format) {
                            $filter           = 'tiny_navbar_' . $format;
                            $sources[$format] = $imagineCacheManager->getBrowserPath($assetUrl, $filter);
                        }
                    }

                    return [
                        'id'       => $user->getId(),
                        'username' => $user->getUsername(),
                        'url'      => $this->generateUrl('app_profil_seeOtherUser', ['id' => $user->getId()]),
                        'image'    => $onDisk ? $packages->getUrl($path) : null,
                        'sources'  => $sources,
                    ];
                },
                $users
            )
        );
    }
}
