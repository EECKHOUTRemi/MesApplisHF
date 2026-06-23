<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/** Point d'entrée du kernel Symfony. */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
