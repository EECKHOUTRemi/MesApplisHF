<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Teste la page de profil et la mise à jour de la photo de profil :
 * affichage du formulaire, dépôt du fichier, remplacement de l'ancienne
 * photo et rejet des fichiers invalides.
 *
 * Les fichiers déposés pendant un test portent un nom unique et sont retirés
 * en fin de test pour ne pas polluer le dossier réel.
 */
final class ProfilControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/profil/';
    private const PICTURE_PATH = '/profil/photo';

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
    private function profileImagesDirectory(): string
    {
        return (string) static::getContainer()->getParameter('profile_images_directory');
    }

    /**
     * Construit un upload PNG 1x1 valide : la contrainte File vérifie le type réel
     * du fichier, on a donc besoin d'octets PNG authentiques, pas d'un fichier vide.
     *
     * @param string $originalName
     * @return UploadedFile
     */
    private function pngUpload(string $originalName = 'photo.png'): UploadedFile
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
        );
        $path = tempnam(sys_get_temp_dir(), 'pfp_png_');
        file_put_contents($path, $bytes);

        // dernier argument à true : mode test, court-circuite la vérification is_uploaded_file()
        return new UploadedFile($path, $originalName, 'image/png', null, true);
    }

    /**
     * Récupère le token CSRF du formulaire de photo rendu sur la page de profil.
     *
     * @return string
     */
    private function csrfToken(): string
    {
        $crawler = $this->client->request('GET', self::INDEX_PATH);
        $this->assertResponseIsSuccessful();

        return $crawler->filter('input[name="profile_picture[_token]"]')->attr('value');
    }

    /**
     * Poste le formulaire de photo de profil avec le fichier donné (ou sans fichier).
     *
     * @param UploadedFile|null $upload
     * @return void
     */
    private function submitPicture(?UploadedFile $upload): void
    {
        $files = null === $upload ? [] : ['profile_picture' => ['image' => $upload]];

        $this->client->request(
            'POST',
            self::PICTURE_PATH,
            ['profile_picture' => ['_token' => $this->csrfToken()]],
            $files,
        );
    }

    public function testUpdatePictureRequiresAuthentication(): void
    {
        $this->client->request('POST', self::PICTURE_PATH);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/login', (string) $location);
    }

    public function testIndexDisplaysPictureForm(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="' . self::PICTURE_PATH . '"]');
        $this->assertSelectorExists('input[name="profile_picture[image]"]');
    }

    public function testNavbarShowsProfileDropdown(): void
    {
        $this->login($this->createUser());

        $crawler = $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav #profileDropdown[data-bs-toggle="dropdown"]');

        $menu = $crawler->filter('nav ul[aria-labelledby="profileDropdown"]');
        $this->assertSame(1, $menu->count());
        $this->assertStringContainsString('Profil', $menu->text());
        $this->assertStringContainsString('Modifier le profil', $menu->text());
        $this->assertStringContainsString('Se déconnecter', $menu->text());

        // chaque entrée pointe au bon endroit et porte son icône bootstrap
        $this->assertSame(1, $menu->filter('a[href="' . self::INDEX_PATH . '"]')->count());
        $this->assertSame(1, $menu->filter('a[href="/settings/"]')->count());
        $this->assertSame(1, $menu->filter('a[href="/logout"]')->count());
        $this->assertCount(3, $menu->filter('a.dropdown-item i.bi'));
    }

    public function testUpdatePictureStoresFileAndUpdatesUser(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->submitPicture($this->pngUpload());

        $this->assertResponseRedirects(self::INDEX_PATH);

        $this->em()->clear();
        $reloaded = $this->em()->find(User::class, $user->getId());
        $image = (string) $reloaded->getImage();
        $this->assertStringEndsWith('.png', $image);

        $storedFile = $this->profileImagesDirectory() . '/' . $image;
        $this->createdFiles[] = $storedFile;
        $this->assertFileExists($storedFile);

        // la page de profil affiche la confirmation et la navbar rend la photo
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Photo de profil mise à jour');
        $this->assertSelectorExists('nav picture img[alt="Profil"]');
    }

    public function testUpdatePictureReplacesOldFile(): void
    {
        $user = $this->createUser();
        $dir = $this->profileImagesDirectory();
        $this->filesystem->mkdir($dir);
        $oldImage = 'old_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($dir . '/' . $oldImage, 'fake-old-image');
        $this->createdFiles[] = $dir . '/' . $oldImage;
        $user->setImage($oldImage);
        $this->em()->flush();
        $this->login($user);

        $this->submitPicture($this->pngUpload());

        $this->assertResponseRedirects(self::INDEX_PATH);

        $this->em()->clear();
        $newImage = (string) $this->em()->find(User::class, $user->getId())->getImage();
        $this->createdFiles[] = $dir . '/' . $newImage;
        $this->assertNotSame($oldImage, $newImage);

        // l'ancien fichier est supprimé, le nouveau est bien déposé
        $this->assertFileDoesNotExist($dir . '/' . $oldImage);
        $this->assertFileExists($dir . '/' . $newImage);
    }

    public function testUpdatePictureLowercasesExtension(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->submitPicture($this->pngUpload('PHOTO.PNG'));

        $this->assertResponseRedirects(self::INDEX_PATH);

        $this->em()->clear();
        $image = (string) $this->em()->find(User::class, $user->getId())->getImage();
        $this->createdFiles[] = $this->profileImagesDirectory() . '/' . $image;
        $this->assertStringEndsWith('.png', $image);
    }

    public function testUpdatePictureRejectsNonImageFile(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $path = tempnam(sys_get_temp_dir(), 'pfp_txt_');
        file_put_contents($path, 'pas une image');
        $upload = new UploadedFile($path, 'document.txt', 'text/plain', null, true);

        $this->submitPicture($upload);

        $this->assertResponseRedirects(self::INDEX_PATH);

        $this->em()->clear();
        $this->assertNull($this->em()->find(User::class, $user->getId())->getImage());

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', "n'a pas pu être mise à jour");
    }

    public function testUpdatePictureWithoutFileShowsError(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->submitPicture(null);

        $this->assertResponseRedirects(self::INDEX_PATH);

        $this->em()->clear();
        $this->assertNull($this->em()->find(User::class, $user->getId())->getImage());

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', "n'a pas pu être mise à jour");
    }
}
