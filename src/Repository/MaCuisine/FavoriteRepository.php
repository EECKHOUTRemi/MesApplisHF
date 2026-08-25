<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\Favorite;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     * @param Security $security
     */
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * @param Recipe $recipe
     * @param User $user
     * @return bool
     */
    public function isFavorite(Recipe $recipe, User $user): bool
    {
        $qb = $this->createQueryBuilder('favorite')
            ->andWhere('favorite.user = :user')
            ->andWhere('favorite.recipe = :recipe')
            ->setParameter('user', $user)
            ->setParameter('recipe', $recipe)
            ->getQuery()
            ->getResult();
        ;

        return count($qb) > 0;
    }

    /**
     * @return Favorite[]
     */
    public function findAllForConnectedUser(): array
    {
        return $this->createQueryBuilder('favorite')
            ->andWhere('favorite.user = :user')
            ->setParameter('user', $this->security->getUser())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Identifiants des recettes mises en favori par l'utilisateur connecté,
     * pour afficher l'état du bouton favori sans charger les entités.
     *
     * @return int[]
     */
    public function findRecipeIdsForConnectedUser(): array
    {
        $rows = $this->createQueryBuilder('favorite')
            ->select('IDENTITY(favorite.recipe) AS recipeId')
            ->andWhere('favorite.user = :user')
            ->setParameter('user', $this->security->getUser())
            ->getQuery()
            ->getScalarResult()
        ;

        return array_map('intval', array_column($rows, 'recipeId'));
    }
}
