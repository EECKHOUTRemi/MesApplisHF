<?php

namespace App\Tests\Functional;

use Symfony\Component\Mime\Email;

/** Formulaire de contact : affichage, pré-remplissage pour les utilisateurs connectés, envoi et validation. */
class ContactControllerTest extends AppWebTestCase
{
    public function testIndexDisplaysTheForm(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="/contact/send"]');
    }

    public function testIndexPrefillsEmailForLoggedInUser(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $crawler = $this->client->request('GET', '/contact');

        $this->assertSame($user->getEmail(), $crawler->filter('#contact_form_email')->attr('value'));
    }

    public function testSubmittingValidFormSendsEmailAndRedirects(): void
    {
        $this->client->request('GET', '/contact');
        $this->client->submitForm('Envoyer', [
            'contact_form[email]' => 'visiteur@test.local',
            'contact_form[subject]' => 'Bug rencontré',
            'contact_form[content]' => "Le formulaire ne fonctionne pas comme prévu.",
        ]);

        $this->assertResponseRedirects('/contact');

        $this->assertEmailCount(1);
        $messages = $this->getMailerMessages();
        $this->assertInstanceOf(Email::class, $messages[0]);

        $this->assertEmailAddressContains($messages[0], 'to', 'contact@mesapplishf.fr');
        $this->assertEmailAddressContains($messages[0], 'Reply-To', 'visiteur@test.local');
        $this->assertEmailHeaderSame($messages[0], 'Subject', 'Formulaire de contact : Bug rencontré');
        $this->assertEmailTextBodyContains($messages[0], 'Le formulaire ne fonctionne pas comme prévu.');
        $this->assertEmailTextBodyContains($messages[0], 'visiteur@test.local');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Votre message a bien été envoyé');
    }

    public function testSubmittingWithBlankFieldsDoesNotSendEmail(): void
    {
        $this->client->request('GET', '/contact');
        $this->client->submitForm('Envoyer', [
            'contact_form[email]' => '',
            'contact_form[subject]' => '',
            'contact_form[content]' => '',
        ]);

        $this->assertResponseRedirects('/contact');
        $this->assertEmailCount(0);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', "n'a pas pu être envoyé");
    }
}
