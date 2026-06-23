<?php

namespace App\Repository\MaCuisine;

use App\Entity\MaCuisine\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ingredient>
 *
 * Repository des ingrédients MaCuisine avec recherche insensible aux accents et à la casse.
 */
class IngredientRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }

    /**
     * Recherche les ingrédients dont le nom contient $term, sans tenir compte des accents ni de la casse
     * (via la fonction DQL UNACCENT + UPPER côté PostgreSQL).
     *
     * @param string        $term   Fragment de nom recherché
     * @param string[]|null $select Colonnes à sélectionner (noms de champs Doctrine validés contre les métadonnées)
     * @param array{string,string}|null $order [$field, $direction] — direction 'ASC' ou 'DESC'
     * @return Ingredient[]|array<array<string, mixed>>
     * @throws \InvalidArgumentException Si un champ inconnu ou une direction invalide est transmis
     */
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

        $qb->andWhere('UPPER(UNACCENT(i.name)) LIKE UPPER(UNACCENT(:term))')
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
