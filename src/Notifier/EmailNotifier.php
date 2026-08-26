<?php

namespace App\Notifier;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailNotifier
{
    private MailerInterface $mailer;
    private string $defaultFromAddress;
    private string $defaultFromName;
    private string $contactAddress;
    private string $baseUrl;

    /**
     * @param MailerInterface $mailer
     * @param string $defaultFromAddress
     * @param string $defaultFromName
     * @param string $contactAddress
     * @param string $baseUrl
     */
    public function __construct(
        MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM_ADDRESS')] string $defaultFromAddress,
        #[Autowire(env: 'MAILER_FROM_NAME')] string $defaultFromName,
        #[Autowire(env: 'MAILER_CONTACT_ADDRESS')] string $contactAddress,
        #[Autowire(env: 'APP_BASE_URL')] string $baseUrl,
    ) {
        $this->mailer = $mailer;
        $this->defaultFromAddress = $defaultFromAddress;
        $this->defaultFromName = $defaultFromName;
        $this->contactAddress = $contactAddress;
        $this->baseUrl = $baseUrl;
    }

    private static string $signature = "L'équipe de MesApplisHF";
    private static string $application = "MesApplisHF";

    /**
     * @param list<string> $to
     * @param string $subject
     * @param string $content
     * @param array{address: string, name: string}|null $from
     * @param string|null $replyTo
     *
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendEmail(
        array $to,
        string $subject,
        string $content,
        ?array $from = null,
        ?string $replyTo = null
    ): void {
        $email = (new TemplatedEmail())
            ->to(...$to)
            ->subject($subject)
            ->htmlTemplate('emails/notification.html.twig')
            ->context([
                'content' => $content,
                'signature' => self::$signature,
                'application' => self::$application,
            ])
        ;

        if ($from) {
            $email->from(new Address($from['address'], $from['name']));
        } else {
            $email->from(new Address($this->defaultFromAddress, $this->defaultFromName));
        }

        if ($replyTo) {
            $email->replyTo(new Address($replyTo));
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
            $content,
        );
    }

    /**
     * @param string $to
     * @param string $password
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendPasswordChangeRequestEmail(string $to, string $password): void
    {
        $content = '<p>Bonjour,</p>
            <p>Un administrateur a créé un compte avec votre adresse mail. Voici votre mot de passe temporaire :
            <strong>' . $password . '</strong>.</p>
            <p>Nous vous recommandons de le changer en vous connectant via
            <a href="' . $this->baseUrl . '/login">ce lien</a>, en cliquant sur l\'icône de profil en haut à droite
            puis en allant dans <strong>Modifier le profil</strong>. Vous trouverez une section permettant de changer
            de mot de passe où il vous sera demandé de renseigner votre mot de passe actuel (présent ci-dessus) puis de
            renseigner deux fois votre nouveau mot de passe.</p>
            <p>À la suite de cette démarche, vous pourrez modifier votre profil plus précisemment et commencer à
            naviguer à travers nos divers outils.</p>
            <p>Si c\'est une erreur, veuillez nous contacter via
            <a href="' . $this->baseUrl . '/contact">ce lien de contact</a>.</p>
        ';

        $this->sendEmail(
            [$to],
            'Nouveau compte créé',
            $content,
        );
    }

    /**
     * Transmet un message envoyé depuis le formulaire de contact à l'équipe MesApplisHF.
     *
     * @param string $from
     * @param string $subject
     * @param string $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendContactEmail(string $from, string $subject, string $message): void
    {
        $content = '<p>Message envoyé par <strong>' . htmlspecialchars($from, ENT_QUOTES) . '</strong> :</p>
            <p>' . nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</p>';

        $this->sendEmail(
            [$this->contactAddress],
            'Formulaire de contact : ' . $subject,
            $content,
            ['name' => $this->defaultFromName, 'address' => $this->contactAddress],
            $from,
        );
    }
}
