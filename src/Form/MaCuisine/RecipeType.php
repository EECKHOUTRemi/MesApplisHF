<?php

namespace App\Form\MaCuisine;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Recipe;
use App\Form\ChoiceList\PassthroughChoiceLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints as Assert;


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
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'maxLength' => 30
                ]
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '1024k',
                        extensions: ['avif', 'webp', 'jpeg', 'jpg', 'png'],
                        extensionsMessage: 'Veuillez fournir une image valide.',
                    )
                ]
            ])
            ->add('utensil', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'multiple' => true,
                'choice_loader' => new PassthroughChoiceLoader(),
                'choice_value' => fn ($v) => (string) $v,
                'attr' => [
                    'class' => 'utensils-select',
                ],
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('source', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Regex(
                        pattern: '/\b((https?|ftp):\/\/)?([a-z0-9-]{2,}\.)+[a-z]{2,}(\/\S*)?/i',
                        match: false,
                        message: 'La source ne doit pas contenir de lien ou d\'adresse web.',
                    ),
                ],
            ])
            ->add('ingredients', ChoiceType::class, [
                'required' => false,
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
