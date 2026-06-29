<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Teste les pages vitrine publiques (/discover) et l'aiguillage de la racine « / »
 * selon l'état de connexion : vitrine pour les anonymes, tableau de bord pour les connectés.
 */
class BrochureControllerTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function brochurePathProvider(): iterable
    {
        yield 'présentation' => ['/discover'];
        yield 'MaCuisine' => ['/discover/macuisine'];
        yield 'MonPoids' => ['/discover/monpoids'];
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('brochurePathProvider')]
    public function testBrochurePagesArePublic(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('brochurePathProvider')]
    public function testBrochurePagesAreAccessibleForLoggedUser(string $path): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    public function testRootRedirectsAnonymousToBrochure(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/discover');
    }

    public function testRootRedirectsLoggedUserToHome(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/home');
    }

    public function testOverviewLinksToBothToolPages(): void
    {
        $this->client->request('GET', '/discover');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/discover/macuisine"]');
        $this->assertSelectorExists('a[href="/discover/monpoids"]');
    }
}
