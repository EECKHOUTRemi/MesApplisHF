<?php

namespace App\EventListener;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Utensil;
use App\Handler\TextFormatter;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Normalise le nom des entrées de référence simples (ingrédients, ustensiles,
 * catégories) avant leur écriture en base, quel que soit le point d'entrée
 * (formulaire recette, CRUD admin, fixtures...).
 * Les textes longs (description de recette, etc.) ne sont volontairement pas concernés.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
readonly class NameFormatterListener
{
    /**
     * @param TextFormatter $textFormatter
     */
    public function __construct(private TextFormatter $textFormatter)
    {
    }

    /**
     * @param PrePersistEventArgs $args
     * @return void
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->supports($entity) || $entity->getName() === null) {
            return;
        }

        $entity->setName($this->textFormatter->format($entity->getName()));
    }

    /**
     * @param PreUpdateEventArgs $args
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->supports($entity) || !$args->hasChangedField('name')) {
            return;
        }

        $formatted = $this->textFormatter->format((string) $args->getNewValue('name'));
        // en preUpdate, seul setNewValue() est pris en compte dans le changeset
        $args->setNewValue('name', $formatted);
        $entity->setName($formatted);
    }

    /**
     * @param object $entity
     * @return bool
     * @phpstan-assert-if-true Ingredient|Utensil|Category $entity
     */
    private function supports(object $entity): bool
    {
        return $entity instanceof Ingredient
            || $entity instanceof Utensil
            || $entity instanceof Category;
    }
}
