<?php

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Galerie admin des photos de profil : contrôle d'accès, listing du dossier
 * public/uploads/pfp et suppression de fichiers.
 *
 * Les fichiers déposés pendant un test portent un nom unique et sont retirés
 * en fin de test pour ne pas polluer le dossier réel.
 */
final class PfpControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/admin/pfp';

    private Filesystem $filesystem;

    /** @var list<string> Chemins absolus des fichiers créés, nettoyés en fin de test. */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            $this->filesystem->remove($path);
        }
        $this->createdFiles = [];

        parent::tearDown();
    }

    /** @return string */
    private function imagesDirectory(): string
    {
        return (string) static::getContainer()->getParameter('profile_images_directory');
    }

    /**
     * Dépose un fichier dans le dossier des photos de profil et le suit pour nettoyage.
     *
     * @param string $extension Extension du fichier (sans le point)
     * @return string Nom du fichier créé
     */
    private function createImageFile(string $extension = 'png'): string
    {
        $dir = $this->imagesDirectory();
        $this->filesystem->mkdir($dir);

        $filename = 'test_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $path = $dir . '/' . $filename;
        file_put_contents($path, 'fake-image-content');
        $this->createdFiles[] = $path;

        return $filename;
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', self::INDEX_PATH);

        self::assertResponseStatusCodeSame(302);
    }

    public function testIndexForbiddenForRegularUser(): void
    {
        $this->login($this->createUser());
        $this->client->request('GET', self::INDEX_PATH);

        self::assertResponseStatusCodeSame(403);
    }

    public function testIndexDisplaysGallery(): void
    {
        $this->login($this->createAdmin());
        $this->client->request('GET', self::INDEX_PATH);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Photos de profil');
    }

    public function testIndexListsStoredImages(): void
    {
        $filename = $this->createImageFile();
        $this->login($this->createAdmin());

        $this->client->request('GET', self::INDEX_PATH);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $filename);
    }

    public function testIndexIgnoresNonImageFiles(): void
    {
        $filename = $this->createImageFile('txt');
        $this->login($this->createAdmin());

        $this->client->request('GET', self::INDEX_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            $filename,
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $this->client->request('POST', self::INDEX_PATH . '/delete/whatever.png');

        self::assertResponseStatusCodeSame(302);
    }

    public function testDeleteForbiddenForRegularUser(): void
    {
        $this->login($this->createUser());
        $this->client->request('POST', self::INDEX_PATH . '/delete/whatever.png');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteRemovesImage(): void
    {
        $filename = $this->createImageFile();
        $path = $this->imagesDirectory() . '/' . $filename;
        $this->login($this->createAdmin());

        // GET the gallery first so a session exists and the delete form renders with a CSRF token.
        $crawler = $this->client->request('GET', self::INDEX_PATH);
        $token = (string) $crawler->filter('input[name="_token"]')->first()->attr('value');

        $this->client->request('POST', self::INDEX_PATH . '/delete/' . $filename, ['_token' => $token]);

        self::assertResponseRedirects(self::INDEX_PATH);
        self::assertFileDoesNotExist($path);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'supprimée');
    }

    public function testDeleteMissingImageShowsDanger(): void
    {
        $this->login($this->createAdmin());

        // A dummy image is needed so the gallery renders at least one delete form (and thus a CSRF token).
        $this->createImageFile();
        $crawler = $this->client->request('GET', self::INDEX_PATH);
        $token = (string) $crawler->filter('input[name="_token"]')->first()->attr('value');

        $filename = 'inexistante_' . bin2hex(random_bytes(4)) . '.png';
        $this->client->request('POST', self::INDEX_PATH . '/delete/' . $filename, ['_token' => $token]);

        self::assertResponseRedirects(self::INDEX_PATH);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'introuvable');
    }

    public function testDeleteWithInvalidCsrfTokenRedirectsToIndexWithDanger(): void
    {
        $filename = $this->createImageFile();
        $path = $this->imagesDirectory() . '/' . $filename;
        $this->login($this->createAdmin());

        $this->client->request('POST', self::INDEX_PATH . '/delete/' . $filename, ['_token' => 'invalide']);

        // Regression check: this used to redirect to the recipe-images gallery instead of
        // its own index (copy-pasted from ImagesController).
        self::assertResponseRedirects(self::INDEX_PATH);
        self::assertFileExists($path);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Jeton CSRF invalide');
    }
}
