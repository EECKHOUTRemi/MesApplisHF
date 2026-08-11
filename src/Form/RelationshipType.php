<?php

namespace App\Form;

use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Formulaire admin de gestion d'une relation entre deux utilisateurs. */
class RelationshipType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // `createdAt` et `updatedAt` sont posés par le contrôleur, comme côté
        // utilisateur : les exposer inviterait à réécrire l'historique à la main.
        $builder
            ->add('user1', EntityType::class, [
                'class'         => User::class,
                'label'         => 'Demandeur',
                'choice_label'  => self::userLabel(...),
                'query_builder' => self::orderedByUsername(...),
            ])
            ->add('user2', EntityType::class, [
                'class'         => User::class,
                'label'         => 'Destinataire',
                'choice_label'  => self::userLabel(...),
                'query_builder' => self::orderedByUsername(...),
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => array_flip(Relationship::STATUS_LABELS),
            ])
        ;
    }

    /**
     * Identifie un compte sans ambiguïté : les pseudos ne sont pas uniques,
     * l'e-mail l'est.
     *
     * @param User $user
     * @return string
     */
    public static function userLabel(User $user): string
    {
        return sprintf('%s (%s)', $user->getUsername(), $user->getEmail());
    }

    /**
     * @param UserRepository $repository
     * @return QueryBuilder
     */
    public static function orderedByUsername(UserRepository $repository): QueryBuilder
    {
        return $repository->createQueryBuilder('u')->orderBy('u.username', 'ASC');
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Relationship::class,
        ]);
    }
}
