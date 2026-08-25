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
     * Nombre de favoris par recette, pour les recettes affichées.
     * Une seule requête agrégée, pour éviter un COUNT par carte du fil.
     *
     * @param Recipe[] $recipes
     * @return array<int, int> Nombre de favoris indexé par identifiant de recette
     */
    public function countByRecipes(array $recipes): array
    {
        if ($recipes === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('favorite')
            ->select('IDENTITY(favorite.recipe) AS recipeId', 'COUNT(favorite.id) AS total')
            ->andWhere('favorite.recipe IN (:recipes)')
            ->setParameter('recipes', $recipes)
            ->groupBy('favorite.recipe')
            ->getQuery()
            ->getScalarResult()
        ;

        return array_map('intval', array_column($rows, 'total', 'recipeId'));
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
