<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Vérifie les règles d'accès : anonymes redirigés vers /login, pages légales publiques,
 * pages admin interdites aux utilisateurs simples et accessibles aux admins.
 */
class AccessControlTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function protectedPathProvider(): iterable
    {
        yield 'accueil' => ['/'];
        yield 'profil' => ['/profil/'];
        yield 'paramètres' => ['/settings/'];
        yield 'MaCuisine accueil' => ['/macuisine/'];
        yield 'MaCuisine recettes' => ['/macuisine/recipe'];
        yield 'MonPoids accueil' => ['/monpoids/'];
        yield 'MonPoids IMC' => ['/monpoids/bmi/'];
        yield 'MonPoids mensurations' => ['/monpoids/measurement/'];
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('protectedPathProvider')]
    public function testAnonymousIsRedirectedToLogin(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/login', (string) $location);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function publicPathProvider(): iterable
    {
        yield 'CGU' => ['/cgu'];
        yield 'confidentialité' => ['/confidentialite'];
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('publicPathProvider')]
    public function testLegalPagesArePublic(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminPathProvider(): iterable
    {
        yield 'utilisateurs admin' => ['admin/user'];
        yield 'relations admin' => ['admin/relationship'];
        yield 'recipes admin' => ['/admin/macuisine/recipe'];
        yield 'ingrédients admin' => ['admin/macuisine/ingredient'];
        yield 'utensils admin' => ['/admin/ma/cuisine/utensil'];
        yield 'categories admin' => ['/admin/ma/cuisine/category'];
        yield 'IMC admin' => ['/admin/monpoids/bmi/'];
        yield 'mensurations admin' => ['/admin/monpoids/measurement/'];
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('adminPathProvider')]
    public function testAdminPagesAreForbiddenForRegularUser(string $path): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', $path);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @param string $path
     * @return void
     */
    #[DataProvider('adminPathProvider')]
    public function testAdminPagesAreAccessibleForAdmin(string $path): void
    {
        $this->login($this->createAdmin());

        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    public function testHomeIsAccessibleForLoggedUser(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
