<?php

namespace App\Tests\Unit\Notifier;

use App\Notifier\EmailNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Teste EmailNotifier : e-mail de tentative d'inscription (voir SecurityController::register)
 * et e-mail du formulaire de contact (voir ContactController::sendMail).
 */
final class EmailNotifierTest extends TestCase
{
    /**
     * @param TemplatedEmail|null $sent
     * @return EmailNotifier
     */
    private function createNotifier(?TemplatedEmail &$sent): EmailNotifier
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use (&$sent): bool {
                $sent = $email;

                return true;
            }));

        return new EmailNotifier(
            $mailer,
            'register@mesapplishf.fr',
            'MesApplisHF',
            'contact@mesapplishf.fr',
            'https://mesapplishf.fr',
        );
    }

    public function testSendRegisterAttemptEmailNotifiesTheEmailOwner(): void
    {
        $sent = null;
        $notifier = $this->createNotifier($sent);

        $notifier->sendRegisterAttemptEmail('titulaire@test.local');

        $this->assertInstanceOf(TemplatedEmail::class, $sent);
        $this->assertSame('Tentative de création de compte', $sent->getSubject());
        $this->assertSame('emails/notification.html.twig', $sent->getHtmlTemplate());

        $to = $sent->getTo();
        $this->assertCount(1, $to);
        $this->assertSame('titulaire@test.local', $to[0]->getAddress());

        $from = $sent->getFrom();
        $this->assertCount(1, $from);
        $this->assertSame('register@mesapplishf.fr', $from[0]->getAddress());
    }

    public function testSendContactEmailNotifiesTheContactAddressAndAllowsReplyingToTheSender(): void
    {
        $sent = null;
        $notifier = $this->createNotifier($sent);

        $notifier->sendContactEmail(
            'visiteur@test.local',
            'Bug rencontré',
            "Le formulaire ne fonctionne pas.\nMerci de votre aide.",
        );

        $this->assertInstanceOf(TemplatedEmail::class, $sent);
        $this->assertSame('Formulaire de contact : Bug rencontré', $sent->getSubject());
        $this->assertSame('emails/notification.html.twig', $sent->getHtmlTemplate());

        // Envoyé à l'adresse de contact de l'équipe, pas au visiteur.
        $to = $sent->getTo();
        $this->assertCount(1, $to);
        $this->assertSame('contact@mesapplishf.fr', $to[0]->getAddress());

        // Le "From" reste l'adresse de contact du site (SPF/DKIM) ; répondre au message
        // renvoie bien au visiteur grâce au Reply-To.
        $from = $sent->getFrom();
        $this->assertCount(1, $from);
        $this->assertSame('contact@mesapplishf.fr', $from[0]->getAddress());

        $replyTo = $sent->getReplyTo();
        $this->assertCount(1, $replyTo);
        $this->assertSame('visiteur@test.local', $replyTo[0]->getAddress());

        $context = $sent->getContext();
        $this->assertStringContainsString('visiteur@test.local', $context['content']);
        $this->assertStringContainsString('Le formulaire ne fonctionne pas.', $context['content']);
    }

    public function testSendContactEmailEscapesHtmlInTheSenderProvidedContent(): void
    {
        $sent = null;
        $notifier = $this->createNotifier($sent);

        $notifier->sendContactEmail('visiteur@test.local', 'Sujet', '<script>alert(1)</script><b>gras</b>');

        $context = $sent->getContext();
        $this->assertStringNotContainsString('<script>', $context['content']);
        $this->assertStringNotContainsString('<b>', $context['content']);
    }
}
