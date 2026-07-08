<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @param string $projectDir Racine du projet, pour résoudre les chemins relatifs au docroot
     */
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ) {
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest(
                'ondisk',
                fn (string $filename): bool =>
                    file_exists($this->projectDir . '/public/' . ltrim($filename, '/'))
            ),
        ];
    }
}
