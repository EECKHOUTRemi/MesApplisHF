<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Recherche des utilisateurs par pseudo (insensible à la casse).
     *
     * @param string $query Terme de recherche (min. 2 caractères recommandé)
     * @param UserInterface $exclude Utilisateur à exclure des résultats (l'utilisateur connecté)
     * @param int $limit Nombre maximum de résultats
     * @return User[]
     */
    public function searchByUsername(string $query, UserInterface $exclude, int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.username) LIKE LOWER(:query)')
            ->andWhere('u != :exclude')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('exclude', $exclude)
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user
     * @param string $newHashedPassword
     * @return void
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
