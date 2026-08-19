<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie l'état du compte lors de l'authentification : interdit la connexion
 * tant que l'adresse e-mail n'a pas été confirmée et renvoie alors un nouveau lien.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * @param EmailVerifier $emailVerifier
     */
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    /**
     * @param UserInterface $user
     * @return void
     */
    public function checkPreAuth(UserInterface $user): void
    {
    }

    /**
     * Exécuté après vérification du mot de passe : bloque les comptes non confirmés
     * et leur renvoie un e-mail de confirmation.
     *
     * @param UserInterface $user
     * @return void
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User || $user->isVerified()) {
            return;
        }

        $this->emailVerifier->sendConfirmationEmail($user);

        throw new CustomUserMessageAccountStatusException(
            'Votre adresse e-mail n\'est pas encore confirmée.' .
            'Un nouveau lien de confirmation vient de vous être envoyé.'
        );
    }
}
