<?php

namespace App\Repository\macuisine;

use App\Entity\macuisine\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ingredient>
 */
class IngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }

    public function findNameLike(string $term, ?array $select = null, ?array $order = null): array
    {
        $allowedFields = $this->getClassMetadata()->getFieldNames();
        $assertField = static function (string $field) use ($allowedFields): string {
            if (!in_array($field, $allowedFields, true)) {
                throw new \InvalidArgumentException(sprintf('Unknown field "%s".', $field));
            }
            return $field;
        };

        $qb = $this->createQueryBuilder('i');

        if ($select) {
            $qb->select(array_map(static fn (string $c) => 'i.'.$assertField($c), $select));
        }

        $qb->andWhere('UNACCENT(UPPER(i.name)) LIKE UNACCENT(UPPER(:term))')
            ->setParameter('term', '%'.$term.'%')
        ;

        if ($order) {
            $direction = strtoupper($order[1]);
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid order direction "%s".', $order[1]));
            }
            $qb->orderBy('i.'.$assertField($order[0]), $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
