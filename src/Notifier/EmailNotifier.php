<?php

namespace App\Notifier;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailNotifier
{
    private MailerInterface $mailer;

    /**
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    private static string $signature = "L'équipe de MesApplisHF";
    private static string $application = "MesApplisHF";
    private static string $defaultFromAddress = 'register@mesapplishf.fr';
    private static string $defaultFromName = 'MesApplisHF';

    /**
     * @param array $to
     * @param string $subject
     * @param string $template
     * @param string $content
     * @param array|null $from
     * @param string|null $attachmentPath
     *
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendEmail(
        array $to,
        string $subject,
        string $template,
        string $content,
        ?array $from = null,
        ?string $attachmentPath = null
    ): void {
        $email = (new TemplatedEmail())
            ->to(...$to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'content' => $content,
                'signature' => self::$signature,
                'application' => self::$application,
            ])
        ;

        if ($from) {
            $email->from(new Address($from['address'], $from['name']));
        } else {
            $email->from(new Address(self::$defaultFromAddress, self::$defaultFromName));
        }

        if ($attachmentPath) {
            $email->attachFromPath($attachmentPath, basename($attachmentPath));
        }

        $this->mailer->send($email);
    }

    /**
     * Prévient le titulaire d'une adresse e-mail qu'une inscription a été tentée avec celle-ci.
     *
     * Volontairement silencieux côté formulaire (voir SecurityController::register) : révéler
     * qu'un compte existe déjà pour cette adresse permettrait d'énumérer les comptes existants.
     * Le titulaire, lui, est notifié directement.
     *
     * @param string $to
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendRegisterAttemptEmail(string $to): void
    {
        $content = '<p>Bonjour,</p>
            <p>Quelqu\'un a tenté de créer un compte avec votre adresse e-mail.</p>
            <p>Si c\'est vous, vous avez probablement déjà un compte : essayez simplement de vous
            connecter avec cette adresse.</p>
            <p>Si vous n\'êtes pas à l\'origine de cette tentative, vous pouvez ignorer ce message
            sans risque : aucun compte n\'a été créé et votre mot de passe n\'a pas été modifié.</p>';

        $this->sendEmail(
            [$to],
            'Tentative de création de compte',
            'emails/notification.html.twig',
            $content,
        );
    }
}
