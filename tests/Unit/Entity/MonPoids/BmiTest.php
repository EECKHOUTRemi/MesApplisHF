<?php

namespace App\Tests\Unit\Entity\MonPoids;

use App\Entity\MonPoids\Bmi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Teste le calcul de l'IMC dans Bmi::setBmi() : formule, arrondi et écrasement de valeur. */
class BmiTest extends TestCase
{
    /**
     * @return iterable<string, array{float, float, float}>
     */
    public static function bmiProvider(): iterable
    {
        // taille (cm), poids (kg), IMC attendu = poids / (taille en m)²
        yield 'valeur normale' => [180.0, 80.0, 24.69];
        yield 'maigreur' => [175.0, 50.0, 16.33];
        yield 'surpoids' => [165.0, 75.0, 27.55];
        yield 'obésité' => [160.0, 100.0, 39.06];
        yield 'arrondi à 2 décimales' => [170.0, 70.0, 24.22];
        yield 'taille avec décimale' => [172.5, 68.0, 22.85];
    }

    /**
     * @param float $height
     * @param float $weight
     * @param float $expected
     * @return void
     */
    #[DataProvider('bmiProvider')]
    public function testSetBmiComputesBodyMassIndex(float $height, float $weight, float $expected): void
    {
        $bmi = new Bmi();

        $bmi->setBmi($height, $weight);

        $this->assertSame($expected, $bmi->getBmi());
    }

    public function testSetBmiReturnsSelf(): void
    {
        $bmi = new Bmi();

        $this->assertSame($bmi, $bmi->setBmi(180.0, 80.0));
    }

    public function testSetBmiOverwritesPreviousValue(): void
    {
        $bmi = new Bmi();
        $bmi->setBmi(180.0, 80.0);

        $bmi->setBmi(180.0, 90.0);

        $this->assertSame(27.78, $bmi->getBmi());
    }
}
