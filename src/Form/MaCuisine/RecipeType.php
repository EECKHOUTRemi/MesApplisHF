<?php

namespace App\Form\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Recipe;
use App\Form\ChoiceList\PassthroughChoiceLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire utilisateur de recette MaCuisine.
 * Les champs `utensil` et `ingredients` sont non mappés et utilisent PassthroughChoiceLoader
 * pour accepter des valeurs librement soumises par JS (ids existants ou noms libres).
 * Le mapping réel est géré par RecipeFormHandler::persistAndFlush().
 */
class RecipeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('utensil', ChoiceType::class, [
                'mapped' => false,
                'multiple' => true,
                'choice_loader' => new PassthroughChoiceLoader(),
                'choice_value' => fn ($v) => (string) $v,
                'attr' => [
                    'class' => 'utensils-select',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'maxLength' => 30
                ]
            ])
            ->add('description', TextareaType::class)
            ->add('ingredients', ChoiceType::class, [
                'mapped' => false,
                'multiple' => true,
                'choice_loader' => new PassthroughChoiceLoader(),
                'choice_value' => fn ($v) => (string) $v,
                'attr' => [
                    'class' => 'ingredients-select',
                ],
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
