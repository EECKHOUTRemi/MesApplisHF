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
    public function removeImage(string $filename)
    {
        $path = $this->recipesImagesDirectory . '/' . $filename;
        if (is_file($path)) {
            $this->filesystem->remove($path);
        } else {
            throw new FileNotFoundException("Image $filename not found");
        }
    }
}
