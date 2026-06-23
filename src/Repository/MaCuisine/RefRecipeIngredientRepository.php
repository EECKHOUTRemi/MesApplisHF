<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\RefRecipeIngredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefRecipeIngredient>
 */
class RefRecipeIngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefRecipeIngredient::class);
    }
}
