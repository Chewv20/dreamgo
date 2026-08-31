<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Slugify;
use PHPUnit\Framework\TestCase;

/**
 * Slugify::generar() produce el slug de paquetes y articulos, que va en la URL publica
 * (`/paquetes/{slug}`) y ademas alimenta la deteccion de duplicados de slugUnico() en los
 * controladores admin. Auditoria 2026-08-31, hallazgo SEG-08: nunca debe devolver cadena
 * vacia (ficha inalcanzable) y un titulo sin equivalente ASCII debe degradar limpio.
 */
final class SlugifyTest extends TestCase
{
    /**
     * @dataProvider casos
     */
    public function testGeneraSlug(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Slugify::generar($entrada));
    }

    public static function casos(): array
    {
        return [
            'basico' => ['Cancún Todo Incluido', 'cancun-todo-incluido'],
            'acentos y enie' => ['Camión a Oaxaca ñ', 'camion-a-oaxaca-n'],
            'simbolos colapsados' => ['10% de descuento!!!', '10-de-descuento'],
            'espacios en los bordes' => ['  Perú Ancestral  ', 'peru-ancestral'],
            'guiones repetidos' => ['a---b', 'a-b'],
        ];
    }

    public function testTituloSinAsciiCaeAlFallback(): void
    {
        $this->assertSame('item', Slugify::generar('日本語'));
        $this->assertSame('articulo', Slugify::generar('日本語', 'articulo'));
    }

    public function testSoloSimbolosCaeAlFallback(): void
    {
        $this->assertSame('paquete', Slugify::generar('!!!', 'paquete'));
        $this->assertSame('item', Slugify::generar(''));
    }
}
