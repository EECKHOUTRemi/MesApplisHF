<?php

namespace App\Form\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Form\ChoiceList\PassthroughChoiceLoader;
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
        $builder->add('name', TextType::class, [
            'attr' => [
                'maxLength' => 15
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
