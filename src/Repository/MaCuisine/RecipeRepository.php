<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 *
 * Repository des recettes MaCuisine.
 */
class RecipeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Recherche des recettes par nom et/ou filtres (ingrédients, ustensiles, catégories,
     * difficulté, budget, temps). Les filtres se combinent en ET ; à l'intérieur d'un filtre
     * multivalué, une recette correspond dès qu'elle contient l'un des éléments sélectionnés.
     * Difficulté, budget et temps sont des plafonds : une recette sans valeur renseignée
     * n'est retenue que si le filtre correspondant n'est pas utilisé.
     *
     * @param string|null $query
     * @param int[]|null $ingredients
     * @param int[]|null $utensils
     * @param int[]|null $category
     * @param int|null $maxDifficulty
     * @param int|null $maxCost
     * @param int|null $maxTime
     * @return Recipe[]
     */
    public function findWithQuery(
        ?string $query,
        ?array $ingredients,
        ?array $utensils,
        ?array $category,
        ?int $maxDifficulty = null,
        ?int $maxCost = null,
        ?int $maxTime = null
    ): array {
        $qb = $this->createQueryBuilder('r');

        if ($query !== null && $query !== '') {
            $qb->andWhere('r.name LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($ingredients) {
            $qb->join('r.refRecipeIngredients', 'rri')
                ->andWhere('IDENTITY(rri.ingredient) IN (:ingredients)')
                ->setParameter('ingredients', $ingredients);
        }

        if ($utensils) {
            $qb->join('r.utensil', 'u')
                ->andWhere('u.id IN (:utensils)')
                ->setParameter('utensils', $utensils);
        }

        if ($category) {
            $qb->andWhere('IDENTITY(r.category) IN (:category)')
                ->setParameter('category', $category);
        }

        if ($maxDifficulty !== null) {
            $qb->andWhere('r.difficulty <= :maxDifficulty')
                ->setParameter('maxDifficulty', $maxDifficulty);
        }

        if ($maxCost !== null) {
            $qb->andWhere('r.cost <= :maxCost')
                ->setParameter('maxCost', $maxCost);
        }

        if ($maxTime !== null) {
            $qb->andWhere('r.time <= :maxTime')
                ->setParameter('maxTime', $maxTime);
        }

        return $qb->distinct()
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recettes proposées à la sélection dans le chat : toutes les recettes sont
     * consultables par tous, mais celles de l'utilisateur remontent en tête.
     *
     * @param string|null $term
     * @param User $author
     * @param int $limit
     * @return Recipe[]
     */
    public function findForAttachment(?string $term, User $author, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('r')
            // HIDDEN : sert uniquement au tri, la requête retourne bien des Recipe.
            ->addSelect('CASE WHEN r.author = :author THEN 0 ELSE 1 END AS HIDDEN mine')
            ->setParameter('author', $author)
            ->orderBy('mine', 'ASC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($term !== null && $term !== '') {
            $qb->andWhere('LOWER(r.name) LIKE :term')
                ->setParameter('term', '%' . mb_strtolower($term) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
