<?php

namespace App\Tests\Unit\Notifier;

use App\Notifier\EmailNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Teste EmailNotifier::sendRegisterAttemptEmail : l'e-mail envoyé au titulaire d'une adresse
 * quand quelqu'un tente de s'inscrire avec (voir SecurityController::register).
 */
final class EmailNotifierTest extends TestCase
{
    public function testSendRegisterAttemptEmailNotifiesTheEmailOwner(): void
    {
        $sent = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use (&$sent): bool {
                $sent = $email;

                return true;
            }));

        $notifier = new EmailNotifier($mailer);
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
}
