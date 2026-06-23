<?php

namespace App\Form\MonPoids;

use App\Entity\MonPoids\Measurement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Formulaire de saisie de mensurations corporelles (poitrine, hanches, cuisse, taille, date).
 * Une contrainte de formulaire globale exige qu'au moins un champ de mesure soit renseigné.
 */
class MeasurementType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chest', NumberType::class)
            ->add('hips', NumberType::class)
            ->add('thigh', NumberType::class)
            ->add('waist', NumberType::class)
            // l'entité attend un DateTimeImmutable ; par défaut DateType produit un DateTime
            ->add('createdAt', DateType::class, [
                'input' => 'datetime_immutable',
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
