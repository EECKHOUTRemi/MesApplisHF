<?php

namespace App\Twig;

use App\Entity\User;
use App\Mercure\ChatTopics;
use App\Repository\Friends\MessageRepository;
use App\Repository\Friends\RelationshipRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @param string $projectDir Racine du projet, pour résoudre les chemins relatifs au docroot
     * @param RelationshipRepository $relationshipRepository
     * @param MessageRepository $messageRepository
     * @param Security $security
     */
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        private readonly RelationshipRepository $relationshipRepository,
        private readonly MessageRepository $messageRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_friend_requests_count', $this->pendingFriendRequestsCount(...)),
            new TwigFunction('unread_messages_count', $this->unreadMessagesCount(...)),
            new TwigFunction('chat_topic', $this->chatTopic(...)),
        ];
    }

    /**
     * Retourne le nombre de demandes d'amitié en attente pour l'utilisateur connecté.
     *
     * @return int
     */
    public function pendingFriendRequestsCount(): int
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return 0;
        }

        return $this->relationshipRepository->countPendingFor($user);
    }

    /**
     * Retourne le nombre de messages non lus de l'utilisateur connecté.
     *
     * @return int
     */
    public function unreadMessagesCount(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->messageRepository->countUnreadFor($user);
    }

    /**
     * Retourne le sujet Mercure de l'utilisateur connecté, ou null s'il n'y en a
     * pas : la page n'ouvre alors aucune connexion temps réel.
     *
     * @return string|null
     */
    public function chatTopic(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof User ? ChatTopics::forUser($user) : null;
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest(
                'ondisk',
                fn (string $filename): bool =>
                    file_exists($this->projectDir . '/public/' . ltrim($filename, '/'))
            ),
        ];
    }
}
