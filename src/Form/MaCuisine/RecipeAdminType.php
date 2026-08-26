<?php

namespace App\Form\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire admin simplifié de recette : métadonnées sans gestion d'ingrédients.
 * Utilise `input: 'datetime_immutable'` pour que DateTimeType produise des DateTimeImmutable.
 */
class RecipeAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choiceTypeParams = [
            'required' => false,
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
            'choices' => ['5' => 5, '4' => 4, '3' => 3, '2' => 2, '1' => 1],
        ];

        $builder
            // l'entité attend des DateTimeImmutable ; par défaut DateTimeType produit un DateTime
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('updatedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('source', TextType::class, [
                'required' => false,
            ])
            ->add('portions', IntegerType::class, [
                'required' => false,
            ])
            ->add('time', IntegerType::class, [
                'required' => false,
            ])
            ->add('difficulty', ChoiceType::class, $choiceTypeParams)
            ->add('cost', ChoiceType::class, $choiceTypeParams)
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
