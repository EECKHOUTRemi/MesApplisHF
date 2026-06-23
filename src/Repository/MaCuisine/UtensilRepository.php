<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\Utensil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utensil>
 *
 * Repository des ustensiles MaCuisine.
 */
class UtensilRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utensil::class);
    }
}
