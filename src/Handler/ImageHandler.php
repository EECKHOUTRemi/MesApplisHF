<?php

namespace App\Handler;

use Imagine\Exception\RuntimeException;
use Imagine\Image\ImagineInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageHandler
{
    private string $recipesImagesDirectory;
    private Filesystem $filesystem;
    private string $pfpImagesDirectory;

    public function __construct(
        Filesystem $filesystem,
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory,
        #[Autowire(param: 'profile_images_directory')] string $pfpImagesDirectory,
        private readonly ImagineInterface $imagine,
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

    /**
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @param int $maxDimension
     * @param int $quality
     * @return void
     * @throws RuntimeException Si le fichier fourni n'est pas une image valide (échec de décodage GD).
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory,
        string $filename,
        int $maxDimension = 1600,
        int $quality = 82,
    ): void {
        $this->filesystem->mkdir($directory);
        $image = $this->imagine->open($file->getPathname());
        $size = $image->getSize();

        $ratio = min($maxDimension / $size->getWidth(), $maxDimension / $size->getHeight(), 1);
        $targetBox = $size->scale($ratio);
        $image = $image->resize($targetBox);

        $image->save($directory . '/' . $filename, ['jpeg_quality' => $quality, 'webp_quality' => $quality]);
    }
}
