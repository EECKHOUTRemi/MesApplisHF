<?php

namespace App\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class PassthroughChoiceLoader implements ChoiceLoaderInterface
{
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList([], $value);
    }

    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        return $values;
    }

    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return array_map('strval', $choices);
    }
}
