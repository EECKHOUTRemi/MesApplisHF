<?php

namespace App\Form\ForgottenPwd;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez entrer un nouveau mot de passe',
                        ),
                        new Length(
                            min: 12,
                            max: 4096,
                            // max length allowed by Symfony for security reasons
                            minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères de long',
                        ),
                        new PasswordStrength(
                            message: 'Ce mot de passe est trop faible. Pour le renforcer, allongez-le et mélangez '
                                . 'majuscules, minuscules, chiffres et caractères spéciaux (ex. ! ? # @), '
                                . 'ou utilisez une phrase de passe de plusieurs mots.',
                        ),
                        new NotCompromisedPassword(
                            message: 'Ce mot de passe est apparu dans une fuite de données. '
                                . 'Veuillez en choisir un autre.',
                        ),
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Retappez votre mot de passe',
                ],
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
