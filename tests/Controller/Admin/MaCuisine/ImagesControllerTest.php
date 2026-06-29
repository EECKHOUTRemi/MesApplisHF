<?php

namespace App\Tests\Controller\Admin\MaCuisine;

use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Galerie admin des images de recettes : contrôle d'accès, listing du dossier
 * public/uploads/recipes et suppression de fichiers.
 *
 * Les fichiers déposés pendant un test portent un nom unique et sont retirés
 * en fin de test pour ne pas polluer le dossier réel.
 */
final class ImagesControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/admin/macuisine/images';

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
        return (string) static::getContainer()->getParameter('recipes_images_directory');
    }

    /**
     * Dépose un fichier dans le dossier des images et le suit pour nettoyage.
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
        self::assertSelectorTextContains('h1', 'Images des recettes');
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
        $this->client->request('GET', self::INDEX_PATH . '/delete/whatever.png');

        self::assertResponseStatusCodeSame(302);
    }

    public function testDeleteForbiddenForRegularUser(): void
    {
        $this->login($this->createUser());
        $this->client->request('GET', self::INDEX_PATH . '/delete/whatever.png');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteRemovesImage(): void
    {
        $filename = $this->createImageFile();
        $path = $this->imagesDirectory() . '/' . $filename;
        $this->login($this->createAdmin());

        $this->client->request('GET', self::INDEX_PATH . '/delete/' . $filename);

        self::assertResponseRedirects(self::INDEX_PATH);
        self::assertFileDoesNotExist($path);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'supprimée');
    }

    public function testDeleteMissingImageShowsDanger(): void
    {
        $this->login($this->createAdmin());
        $filename = 'inexistante_' . bin2hex(random_bytes(4)) . '.png';

        $this->client->request('GET', self::INDEX_PATH . '/delete/' . $filename);

        self::assertResponseRedirects(self::INDEX_PATH);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'introuvable');
    }
}
