<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use App\Repository\MaCuisine\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Teste IngredientRepository::findNameLike() : insensibilité aux accents et à la casse,
 * projection de colonnes, tri, et rejet de champs/directions invalides.
 */
class IngredientRepositoryTest extends KernelTestCase
{
    private const ASCII_NAME_SUFFIX = '-banane';

    private IngredientRepository $repository;
    private string $suffix;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(IngredientRepository::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $this->suffix = bin2hex(random_bytes(6));
        // mêmes caractères recherchés dans les deux noms : accentué/majuscule vs ascii/minuscule
        foreach (['Pêche-' . $this->suffix, 'peche-' . $this->suffix . self::ASCII_NAME_SUFFIX] as $name) {
            $ingredient = new Ingredient();
            $ingredient->setName($name);
            $em->persist($ingredient);
        }
        $em->flush();
    }

    public function testFindNameLikeIgnoresCaseAndAccents(): void
    {
        $results = $this->repository->findNameLike('PECHE-' . substr($this->suffix, 0, 4));

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Ingredient::class, $results);
    }

    public function testFindNameLikeWithSelectReturnsArrays(): void
    {
        $results = $this->repository->findNameLike($this->suffix . self::ASCII_NAME_SUFFIX, ['name']);

        $this->assertCount(1, $results);
        $this->assertSame('peche-' . $this->suffix . self::ASCII_NAME_SUFFIX, $results[0]['name']);
    }

    public function testFindNameLikeWithOrderSortsResults(): void
    {
        $results = $this->repository->findNameLike($this->suffix, null, ['name', 'DESC']);

        $names = array_map(static fn (Ingredient $i) => $i->getName(), $results);
        $sorted = $names;
        rsort($sorted);
        $this->assertSame($sorted, $names);
    }

    public function testFindNameLikeRejectsUnknownField(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->findNameLike('x', ['password']);
    }

    public function testFindNameLikeRejectsInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->findNameLike('x', null, ['name', 'SIDEWAYS']);
    }
}
