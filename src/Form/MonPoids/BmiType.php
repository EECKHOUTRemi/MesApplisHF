<?php

namespace App\Form\MonPoids;

use App\Entity\MonPoids\Bmi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BmiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('height', NumberType::class)
            ->add('weight', NumberType::class)
            // l'entité attend un DateTimeImmutable ; par défaut DateType produit un DateTime
            ->add('createdAt', DateType::class, [
                'input' => 'datetime_immutable',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bmi::class,
        ]);
    }
}
