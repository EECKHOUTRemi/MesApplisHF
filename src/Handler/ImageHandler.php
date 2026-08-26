<?php

namespace App\Handler;

use AllowDynamicProperties;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class ImageHandler
{
    private string $recipesImagesDirectory;
    private Filesystem $filesystem;
    private string $pfpImagesDirectory;

    public function __construct(
        Filesystem $filesystem,
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory,
        #[Autowire(param: 'profile_images_directory')] string $pfpImagesDirectory
    ) {
        $this->filesystem = $filesystem;
        $this->recipesImagesDirectory = $recipesImagesDirectory;
        $this->pfpImagesDirectory = $pfpImagesDirectory;
    }

    /**
     * @param string $filename Nom du fichier image à supprimer
     * @return void
     */
    public function removeRecipeImage(string $filename): void
    {
        $safeFilename = basename($filename);
        $path = $this->recipesImagesDirectory . '/' . $safeFilename;

        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Image "%s" not found.', $safeFilename));
        }

        $this->filesystem->remove($path);
    }

    /**
     * @param string $filename Nom du fichier image à supprimer
     * @return void
     */
    public function removePfpImage(string $filename): void
    {
        $safeFilename = basename($filename);
        $path = $this->pfpImagesDirectory . '/' . $safeFilename;

        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Image "%s" not found.', $safeFilename));
        }

        $this->filesystem->remove($path);
    }
}
