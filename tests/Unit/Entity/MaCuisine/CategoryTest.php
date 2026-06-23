<?php

namespace App\Tests\Unit\Entity\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Teste les accesseurs de l'entité Category. */
class CategoryTest extends TestCase
{
    public function testAccessors(): void
    {
        $category = new Category();
        $user = new User();
        $createdAt = new \DateTimeImmutable('2026-01-01');
        $updatedAt = new \DateTimeImmutable('2026-02-01');

        $category->setName('Desserts')
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
            ->setCreatedBy($user);

        $this->assertNull($category->getId());
        $this->assertSame('Desserts', $category->getName());
        $this->assertSame($createdAt, $category->getCreatedAt());
        $this->assertSame($updatedAt, $category->getUpdatedAt());
        $this->assertSame($user, $category->getCreatedBy());
    }
}
