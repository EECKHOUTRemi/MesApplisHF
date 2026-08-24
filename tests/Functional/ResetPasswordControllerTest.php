<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Réinitialisation de mot de passe oublié : demande, e-mail, changement du
 * mot de passe et cas d'erreur (e-mail inconnu, token invalide/absent).
 */
class ResetPasswordControllerTest extends AppWebTestCase
{
    public function testFullResetPasswordFlow(): void
    {
        $user = $this->createUser();

        // Page de demande de réinitialisation
        $this->client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Mot de passe oublié');

        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);

        $this->assertEmailCount(1);
        $messages = $this->getMailerMessages();
        $this->assertCount(1, $messages);

        $this->assertEmailAddressContains($messages[0], 'from', 'resetpwd@mesapplishf.fr');
        $this->assertEmailAddressContains($messages[0], 'to', $user->getEmail());
        $this->assertEmailTextBodyContains($messages[0], 'Ce lien expirera dans 1 heure.');

        $this->assertResponseRedirects('/reset-password/check-email');

        $crawler = $this->client->followRedirect();
        $this->assertPageTitleContains('E-mail envoyé');
        $this->assertStringContainsString('Ce lien expirera dans 1 heure', $crawler->html());

        // Le lien de réinitialisation extrait du corps HTML de l'e-mail doit fonctionner.
        // On lit le corps rendu plutôt que toString() : le payload MIME brut est encodé
        // en quoted-printable, dont les sauts de ligne "soft" tronqueraient le token.
        $this->assertInstanceOf(Email::class, $messages[0]);
        $emailHtml = (string) $messages[0]->getHtmlBody();
        preg_match('#(/reset-password/reset/[a-zA-Z0-9]+)#', $emailHtml, $resetLink);

        $this->client->request('GET', $resetLink[1]);
        $this->assertResponseRedirects('/reset-password/reset');
        $this->client->followRedirect();

        $this->client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => 'newStrongPassword',
            'change_password_form[plainPassword][second]' => 'newStrongPassword',
        ]);

        $this->assertResponseRedirects('/login');

        $refreshedUser = $this->em()->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertInstanceOf(User::class, $refreshedUser);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($passwordHasher->isPasswordValid($refreshedUser, 'newStrongPassword'));
    }

    public function testRequestWithUnknownEmailDoesNotRevealAccountExistence(): void
    {
        $this->client->request('GET', '/reset-password');
        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => uniqid('inconnu.', true) . '@test.local',
        ]);

        // Même redirection que pour un compte existant : ne pas laisser deviner
        // qu'aucun compte n'a été trouvé.
        $this->assertResponseRedirects('/reset-password/check-email');
        $this->assertEmailCount(0);
    }

    public function testASecondRequestIsThrottledWithoutRevealingIt(): void
    {
        $user = $this->createUser();

        $this->client->request('GET', '/reset-password');
        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);
        $this->assertEmailCount(1);

        // Le bundle limite la fréquence des demandes : la seconde n'envoie pas
        // d'e-mail, mais la réponse reste identique pour ne pas l'ébruiter.
        $this->client->request('GET', '/reset-password');
        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);

        // assertEmailCount porte sur la dernière requête : 0 confirme que la
        // seconde demande a bien été bloquée par la limitation de fréquence.
        $this->assertResponseRedirects('/reset-password/check-email');
        $this->assertEmailCount(0);
    }

    public function testResetWithMismatchedPasswordsRedisplaysTheForm(): void
    {
        $user = $this->createUser();

        $this->client->request('GET', '/reset-password');
        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);

        $message = $this->getMailerMessages()[0];
        $this->assertInstanceOf(Email::class, $message);
        preg_match('#(/reset-password/reset/[a-zA-Z0-9]+)#', (string) $message->getHtmlBody(), $resetLink);

        $this->client->request('GET', $resetLink[1]);
        $this->client->followRedirect();

        $this->client->submitForm('Réinitialiser le mot de passe', [
            'change_password_form[plainPassword][first]' => 'unMotDePasseSolide',
            'change_password_form[plainPassword][second]' => 'unAutreMotDePasse',
        ]);

        // Le formulaire est réaffiché avec l'erreur, le mot de passe reste inchangé.
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'Les mots de passe doivent etre identiques.');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $refreshedUser = $this->em()->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertInstanceOf(User::class, $refreshedUser);
        $this->assertFalse($passwordHasher->isPasswordValid($refreshedUser, 'unMotDePasseSolide'));
    }

    public function testRequestWithABlankEmailIsRejected(): void
    {
        $this->client->request('GET', '/reset-password');
        $this->client->submitForm('Envoyer le lien de réinitialisation', [
            'reset_password_request_form[email]' => '',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'Veuillez saisir votre email');
        $this->assertEmailCount(0);
    }

    public function testCheckEmailPageDirectlyGeneratesAFakeToken(): void
    {
        // Accès direct à la page sans être passé par le formulaire de demande :
        // aucun token en session, un faux token doit être généré à la volée.
        $this->client->request('GET', '/reset-password/check-email');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('E-mail envoyé');
    }

    public function testResetWithInvalidTokenRedirectsWithError(): void
    {
        $this->client->request('GET', '/reset-password/reset/un-token-invalide');
        $this->assertResponseRedirects('/reset-password/reset');

        $this->client->followRedirect();

        $this->assertResponseRedirects('/reset-password');
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString(
            'There was a problem validating your password reset request',
            $crawler->html()
        );
    }

    public function testResetWithoutTokenAndWithoutSessionReturns404(): void
    {
        $this->client->request('GET', '/reset-password/reset');

        $this->assertResponseStatusCodeSame(404);
    }
}
