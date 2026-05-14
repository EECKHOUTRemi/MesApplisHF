<?php

namespace App\Form\macuisine;

use App\Entity\macuisine\Recipe;
use App\Entity\User;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
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
