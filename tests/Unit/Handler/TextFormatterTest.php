<?php

namespace App\Tests\Unit\Handler;

use App\Handler\TextFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextFormatterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'majuscule initiale' => ['tomate', 'Tomate'];
        yield 'initiale accentuée' => ['échalote', 'Échalote'];
        yield 'ponctuation retirée' => ['pâte, feuilletée.', 'Pâte feuilletée'];
        yield 'underscore et slash retirés' => ['sel_fin/gros', 'Selfingros'];
        yield 'tiret et apostrophe conservés' => ["huile d'olive-extra", "Huile d'olive-extra"];
        yield 'déjà formaté' => ['Fouet', 'Fouet'];
    }

    /**
     * @param string $input
     * @param string $expected
     * @return void
     */
    #[DataProvider('formatProvider')]
    public function testFormat(string $input, string $expected): void
    {
        $this->assertSame($expected, new TextFormatter()->format($input));
    }
}
