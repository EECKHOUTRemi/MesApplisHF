<?php

namespace App\Controller\admin\MaCuisine;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/macuisine/images'),
IsGranted('ROLE_ADMIN')]
/** Galerie admin de toutes les images stockées dans public/uploads/recipes. */
final class ImagesController extends AbstractController
{
    /**
     * @param string $recipesImagesDirectory Chemin absolu du dossier des images de recettes
     * @return Response
     */
    #[Route(name: 'app_admin_macuisine_images_index', methods: ['GET'])]
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
}
