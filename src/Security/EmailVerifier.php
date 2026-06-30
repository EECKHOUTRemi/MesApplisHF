<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Service d'envoi et de validation des e-mails de confirmation de compte.
 */
class EmailVerifier
{
    /**
     * @param VerifyEmailHelperInterface $verifyEmailHelper
     * @param MailerInterface $mailer
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Construit et envoie l'e-mail de confirmation de compte (expéditeur, sujet et template) à l'utilisateur.
     *
     * @param User $user
     * @return void
     */
    public function sendConfirmationEmail(User $user): void
    {
        $this->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('register@mesapplishf.fr', 'Mes Applis HF - Validation du compte'))
                ->to((string) $user->getEmail())
                ->subject('Confirmez votre adresse e-mail')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    /**
     * Génère l'URL signée de vérification et l'injecte dans le contexte du template avant envoi.
     *
     * @param string $verifyEmailRouteName
     * @param User $user
     * @param TemplatedEmail $email
     * @return void
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => (string) $user->getId()]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return void
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
