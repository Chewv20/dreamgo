<?php

namespace Tests\Unit\Models;

use App\Models\Paquete;
use App\Models\Reserva;
use PHPUnit\Framework\TestCase;

/**
 * WHERE de los buscadores de Reservas y Paquetes del panel. Se prueban sin BD, igual que
 * CotizacionFiltrosTest: lo frágil es el armado condicional y que ningún valor del usuario
 * llegue al SQL sin pasar por un parámetro.
 */
final class ListadosAdminFiltrosTest extends TestCase
{
    public function testReservaSinFiltrosNoHayWhere(): void
    {
        $this->assertSame(['', []], Reserva::clausulaFiltros([]));
        $this->assertSame(['', []], Reserva::clausulaFiltros(['q' => '  ', 'estado' => '', 'salida' => '']));
    }

    public function testReservaBusquedaUsaUnParametroPorColumna(): void
    {
        [$where, $params] = Reserva::clausulaFiltros(['q' => 'ana']);
        $this->assertSame('WHERE (r.codigo_reserva LIKE :q_codigo OR c.nombre LIKE :q_nombre OR c.email LIKE :q_email)', $where);
        $this->assertSame(['q_codigo' => '%ana%', 'q_nombre' => '%ana%', 'q_email' => '%ana%'], $params);
    }

    public function testReservaBusquedaEscapaComodinesDeLike(): void
    {
        [, $params] = Reserva::clausulaFiltros(['q' => '50%_off']);
        $this->assertSame('%50\%\_off%', $params['q_codigo']);
    }

    public function testReservaEstadoValidoEInvalido(): void
    {
        [$where, $params] = Reserva::clausulaFiltros(['estado' => 'confirmada']);
        $this->assertSame('WHERE r.estado = :estado', $where);
        $this->assertSame(['estado' => 'confirmada'], $params);

        $this->assertSame(['', []], Reserva::clausulaFiltros(['estado' => "x' OR 1=1"]));
    }

    public function testReservaFiltroDeSalidaYCombinacion(): void
    {
        [$where] = Reserva::clausulaFiltros(['salida' => 'proximas']);
        $this->assertSame('WHERE s.fecha_salida >= CURDATE()', $where);

        [$where, $params] = Reserva::clausulaFiltros(['estado' => 'pendiente', 'salida' => 'pasadas']);
        $this->assertSame('WHERE r.estado = :estado AND s.fecha_salida < CURDATE()', $where);
        $this->assertSame(['estado' => 'pendiente'], $params);

        $this->assertSame(['', []], Reserva::clausulaFiltros(['salida' => 'ayer']));
    }

    public function testPaqueteFiltros(): void
    {
        $this->assertSame(['', []], Paquete::clausulaFiltrosAdmin([]));

        [$where, $params] = Paquete::clausulaFiltrosAdmin(['q' => 'Cancún', 'estado' => 'borrador', 'categoria' => '3']);
        $this->assertSame('WHERE p.titulo LIKE :q AND p.estado = :estado AND p.categoria_id = :categoria', $where);
        $this->assertSame(['q' => '%Cancún%', 'estado' => 'borrador', 'categoria' => '3'], $params);
    }

    public function testPaqueteIgnoraValoresInvalidos(): void
    {
        $this->assertSame(['', []], Paquete::clausulaFiltrosAdmin(['estado' => 'eliminado', 'categoria' => '3 OR 1=1']));
    }
}
