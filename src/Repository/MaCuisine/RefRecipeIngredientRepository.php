<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\RefRecipeIngredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefRecipeIngredient>
 *
 * Repository de la table de jointure Recipe ↔ Ingredient (quantité + unité).
 */
class RefRecipeIngredientRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefRecipeIngredient::class);
    }
}
