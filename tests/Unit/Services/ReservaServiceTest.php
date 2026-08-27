<?php

namespace Tests\Unit\Services;

use App\Services\ReservaService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Cubre solo el guard de num_personas agregado en la auditoria del 2026-08-25 (hallazgo
 * Alto: sin este chequeo, un num_personas negativo invierte el UPDATE de cupo_disponible en
 * salidas, y un valor fuera de rango dependia de un CHECK de la base de datos como unica
 * defensa). El guard corre ANTES de tocar la base de datos, asi que se puede probar con un
 * PDO simulado que nunca deberia ser invocado si el guard funciona.
 *
 * El resto de ReservaService::crear() (locking con FOR UPDATE, descuentos, transaccion)
 * necesita una base de datos real y queda fuera del alcance de un test unitario puro.
 */
final class ReservaServiceTest extends TestCase
{
    private function servicioConPdoQueNuncaDebeUsarse(): ReservaService
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('prepare');
        $pdo->expects($this->never())->method('query');

        return new ReservaService($pdo);
    }

    /**
     * @dataProvider proveedorDeValoresInvalidos
     */
    public function testCrearRechazaNumPersonasFueraDeRangoAntesDeTocarLaBaseDeDatos(int $numPersonas): void
    {
        $service = $this->servicioConPdoQueNuncaDebeUsarse();

        $this->expectException(RuntimeException::class);

        $service->crear([
            'salida_id' => 1,
            'nombre' => 'Cliente de prueba',
            'email' => 'cliente@example.com',
            'telefono' => '5555555555',
            'num_personas' => $numPersonas,
        ]);
    }

    public static function proveedorDeValoresInvalidos(): array
    {
        return [
            'negativo' => [-5],
            'cero' => [0],
            'excesivo' => [31],
        ];
    }

    /**
     * @dataProvider proveedorDeReferenciasExternas
     */
    public function testParseReferenciaExterna(string $entrada, int $reservaEsperada, string $conceptoEsperado): void
    {
        $this->assertSame([$reservaEsperada, $conceptoEsperado], ReservaService::parseReferenciaExterna($entrada));
    }

    public static function proveedorDeReferenciasExternas(): array
    {
        return [
            'bare id = anticipo (retrocompatible)' => ['13', 13, 'anticipo'],
            'id con concepto saldo' => ['13:saldo', 13, 'saldo'],
            'id con concepto anticipo explicito' => ['13:anticipo', 13, 'anticipo'],
            'concepto desconocido cae en otro' => ['13:loquesea', 13, 'otro'],
            'sufijo vacio = anticipo' => ['13:', 13, 'anticipo'],
            'basura no numerica' => ['abc', 0, 'anticipo'],
        ];
    }
}
