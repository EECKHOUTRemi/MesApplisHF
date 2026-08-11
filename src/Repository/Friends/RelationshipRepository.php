<?php

namespace App\Repository\Friends;

use App\Entity\Friends\Relationship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Relationship>
 *
 * Repository des relations sociales entre utilisateurs.
 */
class RelationshipRepository extends ServiceEntityRepository
{
    private const DQL_STATUS = 'r.status = :status';

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relationship::class);
    }

    /**
     * Retourne toutes les relations, les deux participants déjà chargés et la plus
     * récente en tête. Le `addSelect` évite une requête par ligne à l'affichage.
     *
     * @return Relationship[]
     */
    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u1', 'u2')
            ->leftJoin('r.user1', 'u1')
            ->leftJoin('r.user2', 'u2')
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les amis acceptés d'un utilisateur (dans les deux sens de la relation).
     *
     * @param UserInterface $user
     * @return User[]
     */
    public function findFriendsFor(UserInterface $user): array
    {
        $rows = $this->createQueryBuilder('r')
            ->where('(r.user1 = :user OR r.user2 = :user)')
            ->andWhere(self::DQL_STATUS)
            ->setParameter('user', $user)
            ->setParameter('status', Relationship::STATUS_ACCEPTED)
            ->getQuery()
            ->getResult();

        return array_map(
            fn (Relationship $r) => $r->getuser1() === $user ? $r->getuser2() : $r->getuser1(),
            $rows
        );
    }

    /**
     * Retourne les demandes d'amitié en attente reçues par un utilisateur.
     *
     * @param UserInterface $user
     * @return Relationship[]
     */
    public function findPendingFor(UserInterface $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user2 = :user')
            ->andWhere(self::DQL_STATUS)
            ->setParameter('user', $user)
            ->setParameter('status', Relationship::STATUS_PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les demandes d'amitié en attente reçues par un utilisateur.
     *
     * @param UserInterface $user
     * @return int
     */
    public function countPendingFor(UserInterface $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user2 = :user')
            ->andWhere(self::DQL_STATUS)
            ->setParameter('user', $user)
            ->setParameter('status', Relationship::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne la relation entre deux utilisateurs dans n'importe quel sens, ou null si aucune.
     *
     * @param UserInterface $userA
     * @param UserInterface $userB
     * @return Relationship|null
     */
    public function findRelationShipByUsers(UserInterface $userA, UserInterface $userB): ?Relationship
    {
        return $this->createQueryBuilder('r')
            ->where('(r.user1 = :a AND r.user2 = :b) OR (r.user1 = :b AND r.user2 = :a)')
            ->setParameter('a', $userA)
            ->setParameter('b', $userB)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
