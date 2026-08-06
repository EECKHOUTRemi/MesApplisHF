<?php

namespace App\Tests\Unit\Handler;

use App\Handler\ImageHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/** Teste ImageHandler : suppression d'un fichier présent, exception si le fichier est absent. */
final class ImageHandlerTest extends TestCase
{
    private Filesystem $filesystem;

    private string $directory;

    private ImageHandler $handler;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = sys_get_temp_dir() . '/image_handler_test_' . bin2hex(random_bytes(6));
        $this->filesystem->mkdir($this->directory);
        $this->handler = new ImageHandler($this->filesystem, $this->directory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    public function testRemoveImageDeletesExistingFile(): void
    {
        file_put_contents($this->directory . '/photo.png', 'fake-image');

        $this->handler->removeImage('photo.png');

        $this->assertFileDoesNotExist($this->directory . '/photo.png');
    }

    public function testRemoveImageThrowsWhenFileIsMissing(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->handler->removeImage('inexistante.png');
    }
}
