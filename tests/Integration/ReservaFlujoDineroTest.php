<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ReservaService;
use RuntimeException;

/**
 * Camino de dinero de punta a punta contra una BD real (auditoria 2026-09, CAL-01 /
 * PENDIENTES #1): descuento de cupo con FOR UPDATE, generacion de codigo/token, registro de
 * pagos del webhook con dedup por UNIQUE(referencia_pago), confirmacion segun el anticipo
 * calculado server-side, reposicion de cupo en cancelacion y expiracion, y aplicacion de
 * codigos de descuento con limite de usos.
 */
final class ReservaFlujoDineroTest extends IntegrationTestCase
{
    private function servicio(): ReservaService
    {
        return new ReservaService(self::$db);
    }

    /** @return array<string, mixed> */
    private function datos(int $numPersonas = 2, ?string $codigoDescuento = null): array
    {
        return [
            'salida_id' => self::SALIDA_ID,
            'nombre' => 'Cliente Prueba',
            'email' => 'cliente@example.com',
            'telefono' => '5551234567',
            'num_personas' => $numPersonas,
            'codigo_descuento' => $codigoDescuento,
        ];
    }

    private function insertarDescuento(string $codigo, string $tipo, float $valor, ?int $usoMaximo = null): int
    {
        self::$db->exec(sprintf(
            "INSERT INTO codigos_descuento
                (codigo, tipo, valor, alcance, fecha_inicio, fecha_fin, uso_maximo, activo)
             VALUES
                ('%s', '%s', %F, 'global', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), %s, 1)",
            $codigo,
            $tipo,
            $valor,
            $usoMaximo === null ? 'NULL' : (string) $usoMaximo
        ));

        return (int) self::$db->lastInsertId();
    }

    // --- creacion ----------------------------------------------------------------------

    public function testCrearDescuentaCupoYGeneraCodigoYToken(): void
    {
        $id = $this->servicio()->crear($this->datos(3));

        $reserva = $this->reserva($id);
        self::assertNotFalse($reserva);
        self::assertSame('pendiente', $reserva['estado']);
        self::assertSame(self::CUPO_INICIAL - 3, $this->cupoDisponible());
        self::assertSame(
            'DG-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            $reserva['codigo_reserva']
        );
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $reserva['token_publico']);
        self::assertEqualsWithDelta(3 * self::PRECIO_PAQUETE, (float) $reserva['precio_total'], 0.001);
        self::assertSame(1, $this->contar('clientes', "email = 'cliente@example.com'"));
    }

    public function testCupoInsuficienteNoInsertaNiDescuenta(): void
    {
        try {
            $this->servicio()->crear($this->datos(self::CUPO_INICIAL + 1));
            self::fail('Se esperaba RuntimeException por cupo insuficiente');
        } catch (RuntimeException $e) {
            self::assertStringContainsStringIgnoringCase('cupo', $e->getMessage());
        }

        self::assertSame(self::CUPO_INICIAL, $this->cupoDisponible());
        self::assertSame(0, $this->contar('reservas'));
    }

    public function testSalidaCerradaRechazaLaReserva(): void
    {
        self::$db->exec("UPDATE salidas SET estado = 'cerrada' WHERE id = " . self::SALIDA_ID);

        $this->expectException(RuntimeException::class);
        $this->servicio()->crear($this->datos(1));
    }

    // --- pagos del webhook -----------------------------------------------------------

    public function testPagoQueAlcanzaElAnticipoConfirmaLaReserva(): void
    {
        // precio_total 2000, anticipo 50% => 1000
        $id = $this->servicio()->crear($this->datos(2));

        $resultado = $this->servicio()->registrarPagoAprobado($id, 'MP-OK', 1000.0, 'anticipo');

        self::assertSame(ReservaService::PAGO_CONFIRMO, $resultado);
        $reserva = $this->reserva($id);
        self::assertSame('confirmada', $reserva['estado']);
        self::assertEqualsWithDelta(1000.0, (float) $reserva['monto_pagado'], 0.001);
        self::assertNotNull($reserva['confirmada_en']);
        self::assertSame(1, $this->contar('pagos_reserva', "reserva_id = {$id}"));
    }

    public function testNotificacionDuplicadaNoDuplicaElMontoPagado(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        $this->servicio()->registrarPagoAprobado($id, 'MP-DUP', 1000.0, 'anticipo');

        $resultado = $this->servicio()->registrarPagoAprobado($id, 'MP-DUP', 1000.0, 'anticipo');

        self::assertSame(ReservaService::PAGO_DUPLICADO, $resultado);
        self::assertEqualsWithDelta(1000.0, (float) $this->reserva($id)['monto_pagado'], 0.001);
        self::assertSame(1, $this->contar('pagos_reserva', "reserva_id = {$id}"));
    }

    public function testPagoInsuficienteDejaLaReservaPendiente(): void
    {
        $id = $this->servicio()->crear($this->datos(2)); // anticipo esperado 1000

        $resultado = $this->servicio()->registrarPagoAprobado($id, 'MP-PARCIAL', 200.0, 'anticipo');

        self::assertSame(ReservaService::PAGO_INSUFICIENTE, $resultado);
        self::assertSame('pendiente', $this->reserva($id)['estado']);
        self::assertSame(1, $this->contar('pagos_reserva', "reserva_id = {$id}"));
    }

    public function testPagosAcumuladosLleganAlAnticipoYConfirman(): void
    {
        $id = $this->servicio()->crear($this->datos(2)); // anticipo esperado 1000

        self::assertSame(
            ReservaService::PAGO_INSUFICIENTE,
            $this->servicio()->registrarPagoAprobado($id, 'MP-1', 600.0, 'anticipo')
        );
        self::assertSame(
            ReservaService::PAGO_CONFIRMO,
            $this->servicio()->registrarPagoAprobado($id, 'MP-2', 400.0, 'anticipo')
        );

        $reserva = $this->reserva($id);
        self::assertSame('confirmada', $reserva['estado']);
        self::assertEqualsWithDelta(1000.0, (float) $reserva['monto_pagado'], 0.001);
    }

    public function testPagoDeSaldoSobreReservaConfirmadaQuedaRegistrado(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        $this->servicio()->registrarPagoAprobado($id, 'MP-ANT', 1000.0, 'anticipo'); // confirma

        $resultado = $this->servicio()->registrarPagoAprobado($id, 'MP-SALDO', 1000.0, 'saldo');

        self::assertSame(ReservaService::PAGO_REGISTRADO, $resultado);
        self::assertEqualsWithDelta(2000.0, (float) $this->reserva($id)['monto_pagado'], 0.001);
    }

    public function testPagoConReferenciaExternaInexistenteEsDuplicado(): void
    {
        self::assertSame(
            ReservaService::PAGO_DUPLICADO,
            $this->servicio()->registrarPagoAprobado(99999, 'MP-HUERFANO', 500.0, 'anticipo')
        );
    }

    public function testAbonosParcialesAlSaldoSeAcumulanHastaLiquidar(): void
    {
        $id = $this->servicio()->crear($this->datos(2)); // total 2000
        $this->servicio()->registrarPagoAprobado($id, 'MP-ANT', 1000.0, 'anticipo');

        self::assertSame(ReservaService::PAGO_REGISTRADO, $this->servicio()->registrarPagoAprobado($id, 'MP-AB1', 300.0, 'saldo'));
        self::assertSame(ReservaService::PAGO_REGISTRADO, $this->servicio()->registrarPagoAprobado($id, 'MP-AB2', 700.0, 'saldo'));

        $reserva = $this->reserva($id);
        self::assertSame('confirmada', $reserva['estado']);
        self::assertEqualsWithDelta(2000.0, (float) $reserva['monto_pagado'], 0.001);
        self::assertSame(2, $this->contar('pagos_reserva', "reserva_id = {$id} AND concepto = 'saldo'"));
    }

    // --- pagos manuales (panel) ------------------------------------------------------

    public function testPagoManualQueCubreElAnticipoConfirma(): void
    {
        $id = $this->servicio()->crear($this->datos(2)); // anticipo 1000

        $resultado = $this->servicio()->registrarPagoManual($id, 1000.0, 'transferencia', 'SPEI-123', 'Banorte');

        self::assertSame(ReservaService::PAGO_CONFIRMO, $resultado);
        $reserva = $this->reserva($id);
        self::assertSame('confirmada', $reserva['estado']);
        self::assertSame('transferencia', $reserva['metodo_pago']);
        $pago = self::$db->query("SELECT * FROM pagos_reserva WHERE reserva_id = {$id}")->fetch();
        self::assertSame('manual:transferencia:SPEI-123', $pago['referencia_pago']);
        self::assertSame('anticipo', $pago['concepto']);
        self::assertSame('Banorte', $pago['nota']);
    }

    public function testPagoManualConLaMismaReferenciaNoSeSumaDosVeces(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        $this->servicio()->registrarPagoManual($id, 500.0, 'transferencia', 'SPEI-9');

        self::assertSame(ReservaService::PAGO_DUPLICADO, $this->servicio()->registrarPagoManual($id, 500.0, 'transferencia', 'SPEI-9'));
        self::assertEqualsWithDelta(500.0, (float) $this->reserva($id)['monto_pagado'], 0.001);
    }

    public function testPagosManualesEnEfectivoSinReferenciaSonIndependientes(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        $this->servicio()->registrarPagoManual($id, 1000.0, 'efectivo'); // confirma

        self::assertSame(ReservaService::PAGO_REGISTRADO, $this->servicio()->registrarPagoManual($id, 250.0, 'efectivo'));
        self::assertSame(ReservaService::PAGO_REGISTRADO, $this->servicio()->registrarPagoManual($id, 250.0, 'efectivo'));
        self::assertEqualsWithDelta(1500.0, (float) $this->reserva($id)['monto_pagado'], 0.001);
        self::assertSame(2, $this->contar('pagos_reserva', "reserva_id = {$id} AND concepto = 'saldo'"));
    }

    public function testPagoManualRechazaReservaCanceladaYDatosInvalidos(): void
    {
        $id = $this->servicio()->crear($this->datos(1));
        $this->servicio()->cancelar($id);

        foreach ([[$id, 100.0, 'efectivo'], [$id, 0.0, 'efectivo']] as [$rid, $monto, $metodo]) {
            try {
                $this->servicio()->registrarPagoManual($rid, $monto, $metodo);
                self::fail('Se esperaba RuntimeException');
            } catch (RuntimeException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }

        $otra = $this->servicio()->crear($this->datos(1));
        $this->expectException(RuntimeException::class);
        $this->servicio()->registrarPagoManual($otra, 100.0, 'bitcoin');
    }

    // --- reposicion de cupo --------------------------------------------------------

    public function testCancelarReponeElCupo(): void
    {
        $id = $this->servicio()->crear($this->datos(4));
        self::assertSame(self::CUPO_INICIAL - 4, $this->cupoDisponible());

        self::assertTrue($this->servicio()->cancelar($id));

        self::assertSame('cancelada', $this->reserva($id)['estado']);
        self::assertSame(self::CUPO_INICIAL, $this->cupoDisponible());
    }

    public function testCancelarDosVecesNoReponeDeMas(): void
    {
        $id = $this->servicio()->crear($this->datos(4));
        $this->servicio()->cancelar($id);

        self::assertFalse($this->servicio()->cancelar($id));
        self::assertSame(self::CUPO_INICIAL, $this->cupoDisponible());
    }

    public function testCancelarNoSuperaElCupoMaximo(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        // Simula un ajuste manual del cupo entre la reserva y la cancelacion.
        self::$db->exec('UPDATE salidas SET cupo_disponible = 9 WHERE id = ' . self::SALIDA_ID);

        $this->servicio()->cancelar($id);

        self::assertSame(self::CUPO_INICIAL, $this->cupoDisponible()); // min(cupo_maximo, 9 + 2)
    }

    public function testExpirarVencidasReponeElCupoYMarcaLaReserva(): void
    {
        $id = $this->servicio()->crear($this->datos(4));
        self::$db->exec(
            'UPDATE reservas SET expira_en = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ' . $id
        );

        $this->servicio()->expirarVencidas();

        self::assertSame('expirada', $this->reserva($id)['estado']);
        self::assertSame(self::CUPO_INICIAL, $this->cupoDisponible());
    }

    public function testExpirarNoTocaReservasQueYaNoEstanPendientes(): void
    {
        $id = $this->servicio()->crear($this->datos(2));
        $this->servicio()->registrarPagoAprobado($id, 'MP-CONF', 1000.0, 'anticipo'); // confirma
        self::$db->exec(
            'UPDATE reservas SET expira_en = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ' . $id
        );

        $this->servicio()->expirarVencidas();

        self::assertSame('confirmada', $this->reserva($id)['estado']);
    }

    // --- codigos de descuento ----------------------------------------------------

    public function testDescuentoPorcentajeAplicaYRegistraUso(): void
    {
        $descuentoId = $this->insertarDescuento('PROMO10', 'porcentaje', 10.0);

        $id = $this->servicio()->crear($this->datos(2, 'PROMO10')); // 2000 - 10% = 1800

        self::assertEqualsWithDelta(1800.0, (float) $this->reserva($id)['precio_total'], 0.001);
        self::assertSame(1, (int) self::$db->query(
            'SELECT usos_actuales FROM codigos_descuento WHERE id = ' . $descuentoId
        )->fetchColumn());
        self::assertSame((string) $descuentoId, (string) $this->reserva($id)['codigo_descuento_id']);
    }

    public function testDescuentoMontoFijoNuncaDejaElPrecioNegativo(): void
    {
        $this->insertarDescuento('MENOS5000', 'monto_fijo', 5000.0);

        $id = $this->servicio()->crear($this->datos(2, 'MENOS5000')); // 2000 - 5000 => 0

        self::assertEqualsWithDelta(0.0, (float) $this->reserva($id)['precio_total'], 0.001);
    }

    public function testDescuentoAgotadoRechazaLaSegundaReserva(): void
    {
        $this->insertarDescuento('SOLOUNO', 'porcentaje', 15.0, 1);
        $this->servicio()->crear($this->datos(1, 'SOLOUNO'));

        try {
            $this->servicio()->crear($this->datos(1, 'SOLOUNO'));
            self::fail('Se esperaba RuntimeException por limite de usos');
        } catch (RuntimeException $e) {
            self::assertStringContainsStringIgnoringCase('límite', $e->getMessage());
        }

        // La segunda reserva no debe existir; el cupo solo bajo por la primera.
        self::assertSame(1, $this->contar('reservas'));
        self::assertSame(self::CUPO_INICIAL - 1, $this->cupoDisponible());
    }

    public function testDescuentoInexistenteRechazaLaReserva(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->crear($this->datos(1, 'NOEXISTE'));
    }
}
