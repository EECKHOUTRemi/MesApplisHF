<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Vérifie que le kernel Symfony démarre sans erreur dans l'environnement de test. */
class SmokeTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        self::bootKernel();

        $this->assertSame('test', self::$kernel->getEnvironment());
    }
}
