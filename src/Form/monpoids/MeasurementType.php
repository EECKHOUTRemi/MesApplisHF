<?php

namespace App\Form\monpoids;

use App\Entity\monpoids\Measurement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chest', NumberType::class, [
                    'required' => false
                ]
            )
            ->add('hips', NumberType::class, [
                    'required' => false
                ]
            )
            ->add('thigh', NumberType::class, [
                    'required' => false
                ]
            )
            ->add('waist', NumberType::class, [
                    'required' => false
                ]
            )
            ->add('createdAt', DateType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Measurement::class,
            'constraints' => [
                new Callback(function (Measurement $measurement, ExecutionContextInterface $context) {
                    if ($measurement->getChest() === null
                        && $measurement->getHips() === null
                        && $measurement->getThigh() === null
                        && $measurement->getWaist() === null
                    ) {
                        $context->buildViolation('Veuillez remplir au moins une mesure.')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
