<?php

namespace App\Form\macuisine;

use App\Entity\macuisine\Category;
use App\Entity\macuisine\Recipe;
use App\Entity\macuisine\Utensil;
use App\Form\ChoiceList\PassthroughChoiceLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('utensil', EntityType::class, [
                'class' => Utensil::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
