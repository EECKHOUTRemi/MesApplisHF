<?php

namespace App\Controller;

use App\Entity\Relationship;
use App\Repository\RelationshipRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Date;
use ValueError;

#[Route('/friends', name: 'app_friends_')]
final class FriendsController extends AbstractController
{
    private const ID_REQUIREMENT = '[0-9]+';

    #[Route(name: 'index')]
    public function index(): Response
    {
        return $this->render('friends/index.html.twig', [
            'controller_name' => 'FriendsController',
        ]);
    }

    #[Route('sendRequest/{id}', name: 'sendRequest', methods: ['POST'], requirements: ['id' => self::ID_REQUIREMENT])]
    public function sendRequest(int $id, UserRepository $userRepository, EntityManagerInterface $em){
        $sender = $this->getUser();
        $reciever = $userRepository->find($id);

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

    #[Route('removeRelationship/{user2Id}', name: 'removeRelationship', methods: ['POST'], requirements: ['user2Id' => self::ID_REQUIREMENT])]
    public function removeRelationship(int $user2Id, RelationshipRepository $relationshipRepository, EntityManagerInterface $em){
        /** @var User $user1 */
        $user1 = $this->getUser();

        $relationship = $relationshipRepository->findOneBy(['user1' => $user1, 'user2' => $user2Id]);

        $em->remove($relationship);
        $em->flush();

        return $this->redirectToRoute('app_profil_seeOtherUser', ['id' => $user2Id]);
    }
}
