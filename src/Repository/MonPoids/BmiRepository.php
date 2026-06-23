<?php

namespace App\Repository\MonPoids;

use App\Entity\MonPoids\Bmi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bmi>
 *
 * Repository des enregistrements IMC MonPoids.
 */
class BmiRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bmi::class);
    }
}
