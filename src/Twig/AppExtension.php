<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('ondisk', fn (string $filename): bool => file_exists($filename)),
        ];
    }
}
