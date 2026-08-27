<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Atribucion;
use PHPUnit\Framework\TestCase;

final class AtribucionTest extends TestCase
{
    public function testDevuelveSiempreLasSieteClavesConNullCuandoNoHayNada(): void
    {
        $resultado = Atribucion::sanitizar([]);

        $this->assertSame(
            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer', 'landing_page'],
            array_keys($resultado)
        );
        $this->assertSame([null, null, null, null, null, null, null], array_values($resultado));
    }

    public function testConservaValoresValidosEIgnoraClavesAjenas(): void
    {
        $resultado = Atribucion::sanitizar([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'verano-2026',
            'otra_cosa' => 'inyeccion',
            'estado' => 'convertida',
        ]);

        $this->assertSame('google', $resultado['utm_source']);
        $this->assertSame('cpc', $resultado['utm_medium']);
        $this->assertSame('verano-2026', $resultado['utm_campaign']);
        $this->assertArrayNotHasKey('otra_cosa', $resultado);
        $this->assertArrayNotHasKey('estado', $resultado);
    }

    public function testQuitaSaltosDeLineaYColapsaEspacios(): void
    {
        $resultado = Atribucion::sanitizar([
            'utm_campaign' => "verano\r\n2026\tpromo   final",
        ]);

        $this->assertSame('verano 2026 promo final', $resultado['utm_campaign']);
    }

    public function testRecortaAlLargoDeColumna(): void
    {
        $resultado = Atribucion::sanitizar([
            'utm_source' => str_repeat('a', 200),
            'landing_page' => '/paquetes?' . str_repeat('b', 400),
        ]);

        $this->assertSame(100, mb_strlen($resultado['utm_source']));
        $this->assertSame(255, mb_strlen($resultado['landing_page']));
    }

    public function testCadenasVaciasOSoloEspaciosQuedanEnNull(): void
    {
        $resultado = Atribucion::sanitizar([
            'utm_source' => '',
            'utm_medium' => '   ',
            'utm_term' => "\t\n",
        ]);

        $this->assertNull($resultado['utm_source']);
        $this->assertNull($resultado['utm_medium']);
        $this->assertNull($resultado['utm_term']);
    }

    public function testDesdeFormularioUsaElHeaderRefererComoRespaldo(): void
    {
        $conReferrerDelForm = Atribucion::desdeFormulario(
            ['referrer' => 'https://form.example/pagina'],
            'https://header.example/otra'
        );
        $this->assertSame('https://form.example/pagina', $conReferrerDelForm['referrer']);

        $sinReferrerDelForm = Atribucion::desdeFormulario([], 'https://header.example/otra');
        $this->assertSame('https://header.example/otra', $sinReferrerDelForm['referrer']);

        $sinNada = Atribucion::desdeFormulario([], null);
        $this->assertNull($sinNada['referrer']);
    }
}
