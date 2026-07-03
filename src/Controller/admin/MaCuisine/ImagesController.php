<?php

namespace App\Controller\admin\MaCuisine;

use App\Handler\ImageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/macuisine/images', name: 'app_admin_macuisine_images_'),
IsGranted('ROLE_ADMIN')]
/** Galerie admin de toutes les images stockées dans public/uploads/recipes. */
final class ImagesController extends AbstractController
{
    /**
     * @param string $recipesImagesDirectory Chemin absolu du dossier des images de recettes
     * @return Response
     */
    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory,
    ): Response {
        $images = [];

        if (is_dir($recipesImagesDirectory)) {
            foreach (scandir($recipesImagesDirectory) ?: [] as $file) {
                if (preg_match('/\.(jpe?g|png|gif|webp|avif)$/i', $file)) {
                    $images[] = $file;
                }
            }
        }

        return $this->render('admin/MaCuisine/images/index.html.twig', [
            'images' => $images,
        ]);
    }

    /**
     * @param string $imageToDelete Nom du fichier image à supprimer
     * @param string $recipesImagesDirectory Chemin absolu du dossier des images de recettes
     * @param Filesystem $filesystem
     * @return Response
     */
    #[Route('/delete/{imageToDelete}', name: 'delete', methods: ['GET'])]
    public function delete(
        string $imageToDelete,
        #[Autowire(param: 'recipes_images_directory')] string $recipesImagesDirectory,
        Filesystem $filesystem,
        ImageHandler $imgHandler
    ): Response {
        $filename = basename($imageToDelete);

        try {
            $imgHandler->removeImage($filename, $filesystem, $recipesImagesDirectory);
            $this->addFlash('success', sprintf('Image « %s » supprimée.', $filename));
        } catch (FileNotFoundException $e) {
            $this->addFlash('danger', sprintf('Image « %s » introuvable.', $filename));
        }

        return $this->redirectToRoute('app_admin_macuisine_images_index');
    }
}
