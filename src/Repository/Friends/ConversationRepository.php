<?php

namespace App\Repository\Friends;

use App\Entity\Friends\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * Repository des fils de discussion entre amis.
 */
class ConversationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Retourne les conversations d'un utilisateur, la plus récemment active en premier.
     *
     * @param User $user
     * @return Conversation[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.users', 'participant')
            ->andWhere('participant = :user')
            ->setParameter('user', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la conversation réunissant exactement ces deux utilisateurs, ou null.
     *
     * @param User $userA
     * @param User $userB
     * @return Conversation|null
     */
    public function findOneBetween(User $userA, User $userB): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.users', 'a')
            ->innerJoin('c.users', 'b')
            ->andWhere('a = :userA')
            ->andWhere('b = :userB')
            ->setParameter('userA', $userA)
            ->setParameter('userB', $userB)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
