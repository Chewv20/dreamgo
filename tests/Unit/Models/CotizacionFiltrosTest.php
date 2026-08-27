<?php

namespace Tests\Unit\Models;

use App\Models\Cotizacion;
use PHPUnit\Framework\TestCase;

/**
 * clausulaFiltros() arma el WHERE del listado de cotizaciones a partir de los filtros del
 * panel. Se prueba aislado (sin BD) porque el armado de SQL condicional es fragil.
 */
final class CotizacionFiltrosTest extends TestCase
{
    public function testSinFiltrosNoHayWhere(): void
    {
        $this->assertSame(['', []], Cotizacion::clausulaFiltros([]));
        $this->assertSame(['', []], Cotizacion::clausulaFiltros(['origen' => null, 'asignado' => '', 'seguimiento' => null]));
    }

    public function testFiltroOrigen(): void
    {
        [$where, $params] = Cotizacion::clausulaFiltros(['origen' => 'google']);
        $this->assertSame('WHERE c.utm_source = :origen', $where);
        $this->assertSame(['origen' => 'google'], $params);

        [$where, $params] = Cotizacion::clausulaFiltros(['origen' => Cotizacion::ORIGEN_DIRECTO]);
        $this->assertSame('WHERE c.utm_source IS NULL', $where);
        $this->assertSame([], $params);
    }

    public function testFiltroAsignado(): void
    {
        [$where, $params] = Cotizacion::clausulaFiltros(['asignado' => '7']);
        $this->assertSame('WHERE c.asignado_a = :asignado', $where);
        $this->assertSame(['asignado' => '7'], $params);

        [$where, $params] = Cotizacion::clausulaFiltros(['asignado' => Cotizacion::SIN_ASIGNAR]);
        $this->assertSame('WHERE c.asignado_a IS NULL', $where);

        // valor no numerico se ignora (no se puede inyectar)
        [$where, $params] = Cotizacion::clausulaFiltros(['asignado' => '5 OR 1=1']);
        $this->assertSame('', $where);
        $this->assertSame([], $params);
    }

    public function testFiltroSeguimientoVencidos(): void
    {
        [$where, $params] = Cotizacion::clausulaFiltros(['seguimiento' => 'vencidos']);
        $this->assertStringContainsString('c.seguimiento_en < CURDATE()', $where);
        $this->assertSame([], $params);

        $this->assertSame(['', []], Cotizacion::clausulaFiltros(['seguimiento' => 'otro-valor']));
    }

    public function testFiltrosCombinados(): void
    {
        [$where, $params] = Cotizacion::clausulaFiltros([
            'origen' => 'facebook',
            'asignado' => '3',
            'seguimiento' => 'vencidos',
        ]);

        $this->assertStringStartsWith('WHERE ', $where);
        $this->assertStringContainsString('c.utm_source = :origen', $where);
        $this->assertStringContainsString('c.asignado_a = :asignado', $where);
        $this->assertStringContainsString('c.seguimiento_en < CURDATE()', $where);
        $this->assertSame(['origen' => 'facebook', 'asignado' => '3'], $params);
    }
}
