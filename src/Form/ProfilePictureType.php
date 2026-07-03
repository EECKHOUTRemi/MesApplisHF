<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/** Formulaire de mise à jour de la photo de profil : un unique champ fichier non mappé. */
class ProfilePictureType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Nouvelle photo de profil',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez sélectionner une image.'),
                    new Assert\File(
                        maxSize: '1024k',
                        extensions: [
                            // Certains convertisseurs (ex. convertio.co) écrivent l'AVIF avec un
                            // major_brand ISOBMFF générique ; libmagic le détecte alors comme HEIF.
                            'avif' => ['image/avif', 'image/heif', 'image/heic'],
                            'webp',
                            'jpeg',
                            'jpg',
                            'png',
                        ],
                        extensionsMessage: 'Veuillez fournir une image valide.',
                    ),
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
        $resolver->setDefaults([]);
    }
}
