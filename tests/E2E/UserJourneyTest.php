<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours utilisateur complet, du point de vue HTTP (sans JavaScript) :
 * inscription -> connexion -> suivi d'IMC dans MonPoids -> déconnexion.
 */
class UserJourneyTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testFullJourneyFromRegistrationToBmiTracking(): void
    {
        $email = uniqid('parcours.', true) . '@test.local';
        $password = 'MotDePasseSur123';

        // 1. Inscription
        $this->client->request('GET', '/register');
        $this->assertSelectorTextContains('h1', 'Inscription');
        $this->client->submitForm("S'inscrire", [
            'registration_form[email]' => $email,
            'registration_form[username]' => 'voyageur',
            'registration_form[plainPassword]' => $password,
        ]);
        $this->assertResponseRedirects('/login');

        // 2. Pas encore connecté : l'accueil renvoie vers la connexion
        $crawler = $this->client->followRedirect();
        while ($this->client->getResponse()->isRedirect()) {
            $crawler = $this->client->followRedirect();
        }
        $this->assertSame('/login', parse_url($crawler->getUri(), PHP_URL_PATH));

        // 3. Connexion avec le compte fraîchement créé
        $this->client->submitForm('Se connecter', [
            '_username' => $email,
            '_password' => $password,
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 4. L'historique IMC est vide pour un nouveau compte
        $this->client->request('GET', '/monpoids/bmi/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('tbody', 'Aucun enregistrement');

        // 5. Premier calcul d'IMC : 72 kg pour 1m75 -> 23.51
        $this->client->request('GET', '/monpoids/bmi/new');
        $this->client->submitForm('Enregistrer', [
            'bmi[height]' => '175',
            'bmi[weight]' => '72',
            'bmi[createdAt]' => '2026-06-10',
        ]);
        $this->assertResponseRedirects('/monpoids/bmi/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('tbody', '23.51');

        // 6. Deuxième calcul : la taille est pré-remplie depuis le profil
        $crawler = $this->client->request('GET', '/monpoids/bmi/new');
        $this->assertSame('175', $crawler->filter('#bmi_height')->attr('value'));
        $this->client->submitForm('Enregistrer', [
            'bmi[height]' => '175',
            'bmi[weight]' => '74',
            'bmi[createdAt]' => '2026-06-11',
        ]);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('tbody', '24.16');

        // 7. Déconnexion : les pages protégées redirigent à nouveau vers /login
        $this->client->request('GET', '/logout');
        $this->client->request('GET', '/monpoids/bmi/');
        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/login', $location);
    }
}
