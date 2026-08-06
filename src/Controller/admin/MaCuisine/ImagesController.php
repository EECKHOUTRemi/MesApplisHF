<?php

namespace App\Controller\admin\MaCuisine;

use App\Handler\ImageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Request $request La requête HTTP courante
     * @param string $imageToDelete Nom du fichier image à supprimer
     * @return Response
     */
    #[Route('/delete/{imageToDelete}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, string $imageToDelete, ImageHandler $imgHandler): Response 
    {
        if (!$this->isCsrfTokenValid('delete-image', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_macuisine_images_index');
        }

        $filename = basename($imageToDelete);

        try {
            $imgHandler->removeImage($filename);
            $this->addFlash('success', sprintf('Image « %s » supprimée.', $filename));
        } catch (FileNotFoundException $e) {
            $this->addFlash('danger', sprintf('Image « %s » introuvable.', $filename));
        }

        return $this->redirectToRoute('app_admin_macuisine_images_index');
    }
}
