<?php

namespace App\Repository\Friends;

use App\Entity\Friends\Conversation;
use App\Entity\Friends\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * Repository des messages échangés entre amis.
 */
class MessageRepository extends ServiceEntityRepository
{
    /** Un message « non lu » est un message reçu — jamais un des siens. */
    private const RECEIVED = '(m.author IS NULL OR m.author != :reader)';

    private const UNREAD = 'm.readAt IS NULL';

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Retourne les derniers messages d'une conversation, du plus ancien au plus récent.
     *
     * @param Conversation $conversation
     * @param int $limit
     * @return Message[]
     */
    public function findForConversation(Conversation $conversation, int $limit = 50): array
    {
        $rows = $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.sentAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_reverse($rows);
    }

    /**
     * Retourne le dernier message de chaque conversation, indexé par identifiant de conversation.
     *
     * @param Conversation[] $conversations
     * @return array<int, Message>
     */
    public function findLastByConversation(array $conversations): array
    {
        if ($conversations === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('m')
            ->andWhere('m.conversation IN (:conversations)')
            ->setParameter('conversations', $conversations)
            ->orderBy('m.sentAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();

        $last = [];

        foreach ($rows as $message) {
            $conversationId = $message->getConversation()->getId();

            if ($conversationId !== null && !isset($last[$conversationId])) {
                $last[$conversationId] = $message;
            }
        }

        return $last;
    }

    /**
     * Compte les messages non lus reçus par un utilisateur, indexés par identifiant de conversation.
     *
     * @param Conversation[] $conversations
     * @param User $reader
     * @return array<int, int>
     */
    public function countUnreadByConversation(array $conversations, User $reader): array
    {
        if ($conversations === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.conversation) AS conversationId', 'COUNT(m.id) AS unread')
            ->andWhere('m.conversation IN (:conversations)')
            ->andWhere(self::RECEIVED)
            ->andWhere(self::UNREAD)
            ->setParameter('conversations', $conversations)
            ->setParameter('reader', $reader)
            ->groupBy('m.conversation')
            ->getQuery()
            ->getResult();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['conversationId']] = (int) $row['unread'];
        }

        return $counts;
    }

    /**
     * Compte tous les messages non lus reçus par un utilisateur, toutes
     * conversations confondues (pastille de la barre de navigation).
     *
     * @param User $reader
     * @return int
     */
    public function countUnreadFor(User $reader): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.conversation', 'c')
            ->innerJoin('c.users', 'participant')
            ->andWhere('participant = :reader')
            ->andWhere(self::RECEIVED)
            ->andWhere(self::UNREAD)
            ->setParameter('reader', $reader)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque comme lus les messages reçus par un utilisateur dans une conversation.
     *
     * @param Conversation $conversation
     * @param User $reader
     * @return int
     */
    public function markAsRead(Conversation $conversation, User $reader): int
    {
        return (int) $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->andWhere('m.conversation = :conversation')
            ->andWhere(self::RECEIVED)
            ->andWhere(self::UNREAD)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('conversation', $conversation)
            ->setParameter('reader', $reader)
            ->getQuery()
            ->execute();
    }
}
