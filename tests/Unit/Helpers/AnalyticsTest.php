<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Analytics;
use PHPUnit\Framework\TestCase;

/**
 * Los IDs se leen de $_ENV (.env) y se validan por formato al leer: un valor invalido deja
 * la herramienta apagada y nunca llega a interpolarse en el <script> del partial.
 */
final class AnalyticsTest extends TestCase
{
    private array $envOriginal = [];

    protected function setUp(): void
    {
        foreach (['GA4_MEASUREMENT_ID', 'META_PIXEL_ID'] as $clave) {
            $this->envOriginal[$clave] = $_ENV[$clave] ?? null;
            unset($_ENV[$clave]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envOriginal as $clave => $valor) {
            if ($valor === null) {
                unset($_ENV[$clave]);
            } else {
                $_ENV[$clave] = $valor;
            }
        }
    }

    public function testGa4IdValido(): void
    {
        $_ENV['GA4_MEASUREMENT_ID'] = 'G-ABCD1234EF';
        $this->assertSame('G-ABCD1234EF', Analytics::ga4Id());
    }

    /**
     * @dataProvider ga4Invalidos
     */
    public function testGa4IdInvalidoDevuelveNull(string $valor): void
    {
        $_ENV['GA4_MEASUREMENT_ID'] = $valor;
        $this->assertNull(Analytics::ga4Id());
    }

    public static function ga4Invalidos(): array
    {
        return [
            'vacio' => [''],
            'sin prefijo' => ['ABCD1234'],
            'minusculas' => ['g-abcd1234'],
            'con comilla' => ["G-ABC';alert(1)//"],
            'con etiqueta' => ['G-<script>'],
            'demasiado corto' => ['G-AB'],
        ];
    }

    public function testMetaPixelIdValido(): void
    {
        $_ENV['META_PIXEL_ID'] = '1234567890123456';
        $this->assertSame('1234567890123456', Analytics::metaPixelId());
    }

    /**
     * @dataProvider pixelInvalidos
     */
    public function testMetaPixelIdInvalidoDevuelveNull(string $valor): void
    {
        $_ENV['META_PIXEL_ID'] = $valor;
        $this->assertNull(Analytics::metaPixelId());
    }

    public static function pixelInvalidos(): array
    {
        return [
            'vacio' => [''],
            'con letras' => ['12345abc'],
            'con espacio' => ['123 456'],
            'inyeccion' => ['1"><script>'],
            'corto' => ['123'],
        ];
    }

    public function testHabilitado(): void
    {
        $this->assertFalse(Analytics::habilitado());

        $_ENV['GA4_MEASUREMENT_ID'] = 'G-ABCD1234EF';
        $this->assertTrue(Analytics::habilitado());

        unset($_ENV['GA4_MEASUREMENT_ID']);
        $_ENV['META_PIXEL_ID'] = '1234567890123456';
        $this->assertTrue(Analytics::habilitado());
    }
}
