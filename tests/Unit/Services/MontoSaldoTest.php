<?php

namespace Tests\Unit\Services;

use App\Services\ReservaService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Regla del monto que el cliente paga desde su link de saldo (liquidar o abonar). Es dinero:
 * lo que escriba el cliente nunca debe salir del rango [mínimo, saldo].
 */
final class MontoSaldoTest extends TestCase
{
    public function testModoTotalCobraElSaldo(): void
    {
        $this->assertSame(4500.0, ReservaService::resolverMontoSaldo(4500, 500, 'total', '1'));
    }

    public function testModoDesconocidoSeTrataComoTotal(): void
    {
        $this->assertSame(4500.0, ReservaService::resolverMontoSaldo(4500, 500, 'loquesea', '1'));
    }

    public function testAbonoValido(): void
    {
        $this->assertSame(1200.5, ReservaService::resolverMontoSaldo(4500, 500, 'abono', '1,200.50'));
        $this->assertSame(500.0, ReservaService::resolverMontoSaldo(4500, 500, 'abono', '500'));
    }

    public function testAbonoMayorOIgualAlSaldoCobraExactamenteElSaldo(): void
    {
        $this->assertSame(4500.0, ReservaService::resolverMontoSaldo(4500, 500, 'abono', '4500'));
        $this->assertSame(4500.0, ReservaService::resolverMontoSaldo(4500, 500, 'abono', '99999'));
    }

    public function testSaldoMenorOIgualAlMinimoSeCobraCompleto(): void
    {
        $this->assertSame(300.0, ReservaService::resolverMontoSaldo(300, 500, 'abono', '100'));
    }

    public function testAbonoMenorAlMinimoSeRechaza(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El abono mínimo es de $500.00.');
        ReservaService::resolverMontoSaldo(4500, 500, 'abono', '499.99');
    }

    /** @dataProvider montosInvalidos */
    public function testAbonoInvalidoSeRechaza(string $monto): void
    {
        $this->expectException(RuntimeException::class);
        ReservaService::resolverMontoSaldo(4500, 0, 'abono', $monto);
    }

    public static function montosInvalidos(): array
    {
        return [
            'vacío' => [''],
            'texto' => ['mil pesos'],
            'cero con mínimo 0' => ['0'],
            'negativo' => ['-100'],
        ];
    }

    public function testSinSaldoSeRechaza(): void
    {
        $this->expectException(RuntimeException::class);
        ReservaService::resolverMontoSaldo(0, 500, 'total', '');
    }
}
