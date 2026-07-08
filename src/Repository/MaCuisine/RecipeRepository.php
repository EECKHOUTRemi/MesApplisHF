<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\Recipe;
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
     * Recherche des recettes par nom et/ou filtres (ingrédients, ustensiles, catégories).
     * Les filtres se combinent en ET ; à l'intérieur d'un filtre multivalué, une recette
     * correspond dès qu'elle contient l'un des éléments sélectionnés.
     *
     * @param string|null $query
     * @param int[]|null $ingredients
     * @param int[]|null $utensils
     * @param int[]|null $category
     * @return Recipe[]
     */
    public function findWithQuery(?string $query, ?array $ingredients, ?array $utensils, ?array $category): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($query) {
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

        return $qb->distinct()
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
