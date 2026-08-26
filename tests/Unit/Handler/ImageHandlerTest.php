<?php

namespace App\Tests\Unit\Handler;

use App\Handler\ImageHandler;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Teste ImageHandler : suppression d'un fichier présent, exception si le fichier est absent,
 * pour les deux répertoires gérés (recettes et photos de profil), et la compression/redimensionnement
 * effectué par compressAndStore.
 */
final class ImageHandlerTest extends TestCase
{
    private Filesystem $filesystem;

    private string $recipesDirectory;

    private string $pfpDirectory;

    private ImageHandler $handler;

    /** @var list<string> Chemins des fichiers temporaires créés par un test, nettoyés en fin de test. */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $suffix = bin2hex(random_bytes(6));
        $this->recipesDirectory = sys_get_temp_dir() . '/image_handler_test_recipes_' . $suffix;
        $this->pfpDirectory = sys_get_temp_dir() . '/image_handler_test_pfp_' . $suffix;
        $this->filesystem->mkdir([$this->recipesDirectory, $this->pfpDirectory]);
        $this->handler = new ImageHandler(
            $this->filesystem,
            $this->recipesDirectory,
            $this->pfpDirectory,
            new Imagine(),
        );
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove([$this->recipesDirectory, $this->pfpDirectory, ...$this->tempFiles]);
        $this->tempFiles = [];
    }

    /**
     * Crée un UploadedFile de test contenant une image PNG unie de la taille donnée.
     *
     * @param int $width
     * @param int $height
     * @return UploadedFile
     */
    private function uploadedImage(int $width, int $height): UploadedFile
    {
        $image = (new Imagine())->create(new Box($width, $height), (new RGB())->color('#3366ff'));
        $path = tempnam(sys_get_temp_dir(), 'image_handler_src_') . '.png';
        $image->save($path);
        $this->tempFiles[] = $path;

        // dernier argument à true : mode test, court-circuite la vérification is_uploaded_file()
        return new UploadedFile($path, 'source.png', 'image/png', null, true);
    }

    public function testRemoveRecipeImageDeletesExistingFile(): void
    {
        file_put_contents($this->recipesDirectory . '/photo.png', 'fake-image');

        $this->handler->removeRecipeImage('photo.png');

        $this->assertFileDoesNotExist($this->recipesDirectory . '/photo.png');
    }

    public function testRemoveRecipeImageThrowsWhenFileIsMissing(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->handler->removeRecipeImage('inexistante.png');
    }

    public function testRemovePfpImageDeletesExistingFile(): void
    {
        file_put_contents($this->pfpDirectory . '/avatar.png', 'fake-image');

        $this->handler->removePfpImage('avatar.png');

        $this->assertFileDoesNotExist($this->pfpDirectory . '/avatar.png');
    }

    public function testRemovePfpImageThrowsWhenFileIsMissing(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->handler->removePfpImage('inexistante.png');
    }

    public function testRemoveRecipeImageDoesNotDeleteFromPfpDirectory(): void
    {
        file_put_contents($this->pfpDirectory . '/avatar.png', 'fake-image');

        $this->expectException(FileNotFoundException::class);

        $this->handler->removeRecipeImage('avatar.png');
    }

    public function testRemoveRecipeImageSanitizesPathTraversal(): void
    {
        file_put_contents($this->pfpDirectory . '/avatar.png', 'fake-image');

        // basename() strips the leading "../pfp/" ; only "avatar.png" inside recipesDirectory
        // is considered, so it must not escape to (and delete from) the pfp directory.
        try {
            $this->handler->removeRecipeImage('../pfp/avatar.png');
            $this->fail('Expected a FileNotFoundException.');
        } catch (FileNotFoundException) {
            $this->assertFileExists($this->pfpDirectory . '/avatar.png');
        }
    }

    public function testCompressAndStoreDownscalesAnOversizedImage(): void
    {
        $upload = $this->uploadedImage(3000, 2000);

        $this->handler->compressAndStore($upload, $this->recipesDirectory, 'big.jpg', 1600);

        $path = $this->recipesDirectory . '/big.jpg';
        $this->assertFileExists($path);

        $size = (new Imagine())->open($path)->getSize();
        // ratio conservé, le plus grand côté (largeur) est ramené à maxDimension
        $this->assertSame(1600, $size->getWidth());
        $this->assertSame(1067, $size->getHeight());
    }

    public function testCompressAndStoreDoesNotUpscaleASmallImage(): void
    {
        $upload = $this->uploadedImage(200, 100);

        $this->handler->compressAndStore($upload, $this->recipesDirectory, 'small.jpg', 1600);

        $size = (new Imagine())->open($this->recipesDirectory . '/small.jpg')->getSize();
        $this->assertSame(200, $size->getWidth());
        $this->assertSame(100, $size->getHeight());
    }

    public function testCompressAndStoreCreatesTheDestinationDirectory(): void
    {
        $upload = $this->uploadedImage(50, 50);
        $missingDir = $this->recipesDirectory . '/nested';

        $this->handler->compressAndStore($upload, $missingDir, 'photo.jpg');

        $this->assertFileExists($missingDir . '/photo.jpg');
    }

    public function testCompressAndStoreShrinksFileSize(): void
    {
        // grande image unie : très compressible, la sortie doit être nettement plus légère
        $upload = $this->uploadedImage(2400, 2400);
        $originalSize = filesize($upload->getPathname());

        $this->handler->compressAndStore($upload, $this->pfpDirectory, 'avatar.jpg', 800, 70);

        $compressedSize = filesize($this->pfpDirectory . '/avatar.jpg');
        $this->assertLessThan($originalSize, $compressedSize);
    }
}
