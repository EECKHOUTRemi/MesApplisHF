<?php

namespace App\Handler;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    private Filesystem $filesystem;

    private string $recipesImagesDirectory;

    public function __construct(
        Filesystem $filesystem,
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory
    ) {
        $this->filesystem = $filesystem;
        $this->recipesImagesDirectory = $recipesImagesDirectory;
    }

    /**
     * @param string $filename Nom du fichier image à supprimer
     * @return void
     */
    public function removeImage(string $filename): void
    {
        $safeFilename = basename($filename);
        $path = $this->recipesImagesDirectory . '/' . $safeFilename;

        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Image "%s" not found.', $safeFilename));
        }

        $this->filesystem->remove($path);
    }
}
