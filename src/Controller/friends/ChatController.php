<?php

namespace App\Controller\friends;

use App\Entity\Friends\Conversation;
use App\Entity\Friends\Message;
use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Mercure\ChatTopics;
use App\Repository\Friends\ConversationRepository;
use App\Repository\Friends\MessageRepository;
use App\Repository\Friends\RelationshipRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ConversationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/friends/chat', name: 'app_friends_chat_'), IsGranted('ROLE_USER')]
final class ChatController extends AbstractController
{
    private const ID_REQUIREMENT = '[0-9]+';

    /** Longueur maximale d'un message, alignée sur le `maxlength` du formulaire. */
    private const MAX_LENGTH = 2000;

    /**
     * @param ConversationRepository $conversations
     * @param MessageRepository $messages
     * @param User $me
     * @return Response
     */
    #[Route(name: 'index')]
    public function index(
        ConversationRepository $conversations,
        MessageRepository $messages,
        #[CurrentUser] User $me,
    ): Response {
        $list         = $conversations->findForUser($me);
        $lastMessages = $messages->findLastByConversation($list);
        $unreadCounts = $messages->countUnreadByConversation($list, $me);

        $rows = [];

        foreach ($list as $conversation) {
            $id = $conversation->getId();

            $rows[] = [
                'conversation' => $conversation,
                'friend'       => $conversation->getOtherParticipant($me),
                'lastMessage'  => $lastMessages[$id] ?? null,
                'unreadCount'  => $unreadCounts[$id] ?? 0,
            ];
        }

        return $this->render('friends/chat/index.html.twig', ['rows' => $rows]);
    }

    /**
     * Ouvre la conversation avec un ami, en la créant si elle n'existe pas encore.
     *
     * @param int $id
     * @param UserRepository $users
     * @param RelationshipRepository $relationships
     * @param ConversationRepository $conversations
     * @param EntityManagerInterface $em
     * @param User $me
     * @return Response
     */
    #[Route('/with/{id}', name: 'start', methods: ['POST'], requirements: ['id' => self::ID_REQUIREMENT])]
    public function start(
        int $id,
        UserRepository $users,
        RelationshipRepository $relationships,
        ConversationRepository $conversations,
        EntityManagerInterface $em,
        #[CurrentUser] User $me,
    ): Response {
        $friend = $users->find($id);

        if ($friend === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $relationship = $relationships->findRelationShipByUsers($me, $friend);

        if ($relationship?->getStatus() !== Relationship::STATUS_ACCEPTED) {
            throw $this->createAccessDeniedException('Vous devez être amis pour démarrer une conversation.');
        }

        $conversation = $conversations->findOneBetween($me, $friend);

        if ($conversation === null) {
            $conversation = (new Conversation())
                ->addUser($me)
                ->addUser($friend);

            $em->persist($conversation);
            $em->flush();
        }

        return $this->redirectToRoute('app_friends_chat_show', ['id' => $conversation->getId()]);
    }

    /**
     * @param Conversation $conversation
     * @param MessageRepository $messages
     * @param User $me
     * @return Response
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => self::ID_REQUIREMENT])]
    #[IsGranted(ConversationVoter::VIEW, subject: 'conversation')]
    public function show(
        Conversation $conversation,
        MessageRepository $messages,
        #[CurrentUser] User $me,
    ): Response {
        $messages->markAsRead($conversation, $me);

        return $this->render('friends/chat/show.html.twig', [
            'conversation' => $conversation,
            'friend'       => $conversation->getOtherParticipant($me),
            'messages'     => $messages->findForConversation($conversation),
        ]);
    }

    /**
     * @param Conversation $conversation
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param HubInterface $hub
     * @param User $me
     * @return Response
     */
    #[Route('/{id}/message', name: 'sendMessage', methods: ['POST'], requirements: ['id' => self::ID_REQUIREMENT])]
    #[IsGranted(ConversationVoter::POST, subject: 'conversation')]
    public function sendMessage(
        Conversation $conversation,
        Request $request,
        EntityManagerInterface $em,
        HubInterface $hub,
        #[CurrentUser] User $me,
    ): Response {
        if (!$this->isCsrfTokenValid('chat_message', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $content = trim($request->request->getString('message'));

        if ($content === '' || mb_strlen($content) > self::MAX_LENGTH) {
            return $this->json(['error' => 'Message invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $message = (new Message())
            ->setConversation($conversation)
            ->setAuthor($me)
            ->setContent($content);

        $conversation->setLastMessageAt($message->getSentAt());

        // Enregistré avant publication : un hub indisponible ne doit pas perdre le message.
        $em->persist($message);
        $em->flush();

        foreach ($conversation->getUsers() as $participant) {
            $hub->publish(new Update(
                ChatTopics::forUser($participant),
                $this->notification($message, $participant),
                private: true,
            ));
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Marque la conversation comme lue, appelé par la page ouverte à la réception
     * d'un message : sans cela le compteur de non-lus resterait à jour au prochain
     * chargement alors que l'utilisateur vient de lire le message.
     *
     * @param Conversation $conversation
     * @param Request $request
     * @param MessageRepository $messages
     * @param User $me
     * @return Response
     */
    #[Route('/{id}/read', name: 'markAsRead', methods: ['POST'], requirements: ['id' => self::ID_REQUIREMENT])]
    #[IsGranted(ConversationVoter::VIEW, subject: 'conversation')]
    public function markAsRead(
        Conversation $conversation,
        Request $request,
        MessageRepository $messages,
        #[CurrentUser] User $me,
    ): Response {
        if (!$this->isCsrfTokenValid('chat_read', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $messages->markAsRead($conversation, $me);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Charge utile poussée à un participant : la bulle déjà rendue pour ce
     * lecteur, plus de quoi rafraîchir la liste des conversations et le compteur
     * de la barre de navigation sans recharger la page.
     *
     * @param Message $message
     * @param User $reader
     * @return string
     */
    private function notification(Message $message, User $reader): string
    {
        $fromMe = $message->getAuthor() === $reader;

        return json_encode([
            'conversationId' => $message->getConversation()->getId(),
            'fromMe'         => $fromMe,
            'preview'        => $message->getContent(),
            'sentAt'         => $message->getSentAt()->format(\DateTimeInterface::ATOM),
            'time'           => $message->getSentAt()->format('H:i'),
            'html'           => $this->renderView('friends/chat/_message.html.twig', [
                'message' => $message,
                'fromMe'  => $fromMe,
                'grouped' => false,
            ]),
        ], JSON_THROW_ON_ERROR);
    }
}
