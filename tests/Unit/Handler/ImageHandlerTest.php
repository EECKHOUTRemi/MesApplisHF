<?php

namespace App\Tests\Unit\Handler;

use App\Handler\ImageHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Teste ImageHandler : suppression d'un fichier présent, exception si le fichier est absent,
 * pour les deux répertoires gérés (recettes et photos de profil).
 */
final class ImageHandlerTest extends TestCase
{
    private Filesystem $filesystem;

    private string $recipesDirectory;

    private string $pfpDirectory;

    private ImageHandler $handler;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $suffix = bin2hex(random_bytes(6));
        $this->recipesDirectory = sys_get_temp_dir() . '/image_handler_test_recipes_' . $suffix;
        $this->pfpDirectory = sys_get_temp_dir() . '/image_handler_test_pfp_' . $suffix;
        $this->filesystem->mkdir([$this->recipesDirectory, $this->pfpDirectory]);
        $this->handler = new ImageHandler($this->filesystem, $this->recipesDirectory, $this->pfpDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove([$this->recipesDirectory, $this->pfpDirectory]);
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
}
