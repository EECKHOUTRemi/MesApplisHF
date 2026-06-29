<?php

namespace App\Tests\Functional;

/**
 * Teste les flux d'authentification : affichage des pages, connexion valide/invalide,
 * inscription avec hachage du mot de passe et envoi d'e-mail de vérification.
 */
class SecurityControllerTest extends AppWebTestCase
{
    public function testLoginPageIsPublic(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
    }

    public function testRegisterPageIsPublic(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Inscription');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'inconnu@test.local',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLoginWithValidCredentialsRedirectsToHome(): void
    {
        $user = $this->createUser(password: 'S3cret!!');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => $user->getEmail(),
            '_password' => 'S3cret!!',
        ]);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSame('/home', parse_url($crawler->getUri(), PHP_URL_PATH));
    }

    public function testRegistrationCreatesUserWithHashedPassword(): void
    {
        $email = uniqid('inscrit.', true) . '@test.local';

        $this->client->request('GET', '/register');
        $this->client->submitForm("S'inscrire", [
            'registration_form[email]' => $email,
            'registration_form[username]' => 'nouvelutilisateur',
            'registration_form[plainPassword]' => 'MotDePasse123',
        ]);

        $this->assertResponseRedirects('/home');

        $user = $this->em()->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user, "L'utilisateur doit exister après l'inscription");
        $this->assertNotSame('MotDePasse123', $user->getPassword(), 'Le mot de passe doit être haché');
        $this->assertFalse($user->isVerified(), "L'e-mail n'est pas encore vérifié à l'inscription");
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRegistrationSendsVerificationEmail(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm("S'inscrire", [
            'registration_form[email]' => uniqid('inscrit.', true) . '@test.local',
            'registration_form[username]' => 'nouvelutilisateur',
            'registration_form[plainPassword]' => 'MotDePasse123',
        ]);

        $this->assertEmailCount(1);
    }
}
