<?php

namespace App\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * ChoiceLoader qui retourne telles quelles les valeurs soumises par le navigateur,
 * sans liste prédéfinie. Utilisé pour les selects JS dynamiques (ingrédients, ustensiles)
 * afin que Symfony ne rejette pas les valeurs injectées côté client.
 */
class PassthroughChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @param ?callable $value
     * @return ChoiceListInterface
     */
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList([], $value);
    }

    /**
     * @param array $values
     * @param ?callable $value
     * @return array
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        return $values;
    }

    /**
     * @param array $choices
     * @param ?callable $value
     * @return array
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return array_map('strval', $choices);
    }
}
