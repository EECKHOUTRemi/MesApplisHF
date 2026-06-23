<?php

namespace App\Tests\Unit\Entity\MaCuisine;

use App\Entity\MaCuisine\Utensil;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Teste les accesseurs de l'entité Utensil. */
class UtensilTest extends TestCase
{
    public function testAccessors(): void
    {
        $utensil = new Utensil();
        $user = new User();
        $createdAt = new \DateTimeImmutable('2026-01-01');
        $updatedAt = new \DateTimeImmutable('2026-02-01');

        $utensil->setName('Fouet')
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
            ->setCreatedBy($user);

        $this->assertNull($utensil->getId());
        $this->assertSame('Fouet', $utensil->getName());
        $this->assertSame($createdAt, $utensil->getCreatedAt());
        $this->assertSame($updatedAt, $utensil->getUpdatedAt());
        $this->assertSame($user, $utensil->getCreatedBy());
    }
}
