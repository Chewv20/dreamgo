<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Models\ConfiguracionSitio;
use App\Models\Reserva;
use Core\Database;
use PDO;
use RuntimeException;

/**
 * Unico punto de la aplicacion que modifica cupo_disponible en salidas.
 * Toda creacion, confirmacion, cancelacion o expiracion de una reserva pasa por aqui
 * para garantizar que el descuento/reposicion de cupo siempre ocurra dentro de una
 * transaccion con bloqueo de fila (evita overbooking por condiciones de carrera).
 */
final class ReservaService
{
    /**
     * Tope de personas por reserva. No es un parametro de negocio configurable (no va en
     * configuracion_sitio): es una cota de sanidad para que un num_personas fuera de rango
     * (negativo, cero, o absurdamente grande) nunca llegue a tocar cupo_disponible ni el
     * INSERT de reservas. Valida aca (no solo en los controladores) porque este es el unico
     * punto de la app que descuenta cupo, y cualquier llamador presente o futuro debe quedar
     * cubierto sin depender de que cada controlador repita la misma validacion.
     */
    private const MAX_PERSONAS_POR_RESERVA = 30;

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @param array{salida_id:int, nombre:string, email:string, telefono:string, num_personas:int,
     *              codigo_descuento?:?string, atribucion?:array<string, string|null>} $datos
     *              'atribucion' (opcional): UTM + referrer + landing_page ya saneados
     *              (App\Helpers\Atribucion). Ausente en reservas creadas desde el panel.
     * @return int id de la reserva creada
     */
    public function crear(array $datos): int
    {
        if ($datos['num_personas'] < 1 || $datos['num_personas'] > self::MAX_PERSONAS_POR_RESERVA) {
            throw new RuntimeException(
                'El numero de personas debe estar entre 1 y ' . self::MAX_PERSONAS_POR_RESERVA . '.'
            );
        }

        return Database::transaction($this->db, function () use ($datos): int {
            $stmtSalida = $this->db->prepare('SELECT * FROM salidas WHERE id = :id FOR UPDATE');
            $stmtSalida->execute(['id' => $datos['salida_id']]);
            $salida = $stmtSalida->fetch();

            if (!$salida) {
                throw new RuntimeException('La fecha de salida no existe.');
            }

            if ($salida['estado'] !== 'abierta') {
                throw new RuntimeException('Esta fecha de salida ya no está disponible.');
            }

            if ((int) $salida['cupo_disponible'] < $datos['num_personas']) {
                throw new RuntimeException('Ya no hay cupo suficiente para esta fecha. Disponible: ' . $salida['cupo_disponible']);
            }

            $clienteId = Cliente::encontrarOCrear($datos['nombre'], $datos['email'], $datos['telefono']);

            $precioUnitario = $salida['precio_override'] !== null
                ? (float) $salida['precio_override']
                : (float) $this->precioBasePaquete((int) $salida['paquete_id']);
            $precioTotal = $precioUnitario * $datos['num_personas'];

            $descuentoId = null;
            if (!empty($datos['codigo_descuento'])) {
                $descuentoService = new DescuentoService($this->db);
                $descuento = $descuentoService->validar($datos['codigo_descuento'], (int) $salida['paquete_id']);
                $precioTotal = $descuentoService->calcularPrecioConDescuento($precioTotal, $descuento);
                $descuentoService->registrarUso((int) $descuento['id']);
                $descuentoId = (int) $descuento['id'];
            }

            $horasExpiracion = (int) ConfiguracionSitio::get('horas_expiracion_reserva', 48);

            $stmtUpdate = $this->db->prepare(
                'UPDATE salidas SET cupo_disponible = cupo_disponible - :personas WHERE id = :id'
            );
            $stmtUpdate->execute(['personas' => $datos['num_personas'], 'id' => $salida['id']]);

            $atr = $datos['atribucion'] ?? [];

            $stmtInsert = $this->db->prepare(
                'INSERT INTO reservas
                    (codigo_reserva, token_publico, salida_id, cliente_id, num_personas, codigo_descuento_id, precio_total, estado, expira_en,
                     utm_source, utm_medium, utm_campaign, utm_term, utm_content, referrer, landing_page)
                 VALUES
                    (:codigo, :token, :salida_id, :cliente_id, :num_personas, :descuento_id, :precio_total, "pendiente", DATE_ADD(NOW(), INTERVAL :horas HOUR),
                     :utm_source, :utm_medium, :utm_campaign, :utm_term, :utm_content, :referrer, :landing_page)'
            );
            $stmtInsert->execute([
                'codigo' => 'TEMP',
                'token' => bin2hex(random_bytes(16)),
                'salida_id' => $salida['id'],
                'cliente_id' => $clienteId,
                'num_personas' => $datos['num_personas'],
                'descuento_id' => $descuentoId,
                'precio_total' => $precioTotal,
                'horas' => $horasExpiracion,
                'utm_source' => $atr['utm_source'] ?? null,
                'utm_medium' => $atr['utm_medium'] ?? null,
                'utm_campaign' => $atr['utm_campaign'] ?? null,
                'utm_term' => $atr['utm_term'] ?? null,
                'utm_content' => $atr['utm_content'] ?? null,
                'referrer' => $atr['referrer'] ?? null,
                'landing_page' => $atr['landing_page'] ?? null,
            ]);

            $reservaId = (int) $this->db->lastInsertId();
            $codigo = 'DG-' . date('Y') . '-' . str_pad((string) $reservaId, 6, '0', STR_PAD_LEFT);

            $stmtCodigo = $this->db->prepare('UPDATE reservas SET codigo_reserva = :codigo WHERE id = :id');
            $stmtCodigo->execute(['codigo' => $codigo, 'id' => $reservaId]);

            return $reservaId;
        });
    }

    /**
     * Crea la reserva y dispara el correo de "reserva pendiente" si se creo bien. Comparte
     * esta orquestacion entre ReservaPublicaController y ReservaAdminController (antes
     * duplicada casi linea por linea en los dos -- auditoria 2026-08-25, hallazgo CAL-02).
     * Lo unico que sigue siendo distinto entre ambos es COMO le muestran el error al usuario
     * si crear() lanza (uno re-renderiza el formulario, el otro redirige con flash), asi que
     * eso se queda en cada controlador en vez de forzarlo aca.
     *
     * @param array{salida_id:int, nombre:string, email:string, telefono:string, num_personas:int, codigo_descuento?:?string} $datos
     * @return array datos completos de la reserva recien creada (Reserva::conDetalle)
     */
    public function crearYNotificar(array $datos): array
    {
        $reservaId = $this->crear($datos);
        $reserva = Reserva::conDetalle($reservaId);
        (new MailerService($this->db))->enviarReservaPendiente($reserva);

        return $reserva;
    }

    public function confirmar(int $reservaId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE reservas SET estado = "confirmada", confirmada_en = NOW() WHERE id = :id AND estado = "pendiente"'
        );
        $stmt->execute(['id' => $reservaId]);

        return $stmt->rowCount() > 0;
    }

    public function cancelar(int $reservaId): bool
    {
        return Database::transaction($this->db, function () use ($reservaId): bool {
            $stmt = $this->db->prepare('SELECT * FROM reservas WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $reservaId]);
            $reserva = $stmt->fetch();

            if (!$reserva || !in_array($reserva['estado'], ['pendiente', 'confirmada'], true)) {
                return false;
            }

            $this->reponerCupo((int) $reserva['salida_id'], (int) $reserva['num_personas']);

            $update = $this->db->prepare(
                'UPDATE reservas SET estado = "cancelada", cancelada_en = NOW() WHERE id = :id'
            );
            $update->execute(['id' => $reservaId]);

            return true;
        });
    }

    /**
     * Tolerancia en la comparacion del monto pagado contra el anticipo esperado, para
     * absorber diferencias de redondeo de centavos entre lo que esta app calcula y lo que
     * Mercado Pago reporta de vuelta.
     */
    private const TOLERANCIA_MONTO_PAGO = 0.01;

    /** Resultados posibles de registrarPagoAprobado(). */
    public const PAGO_CONFIRMO = 'confirmada';
    public const PAGO_REGISTRADO = 'registrada';
    public const PAGO_DUPLICADO = 'duplicada';
    public const PAGO_INSUFICIENTE = 'insuficiente';

    /**
     * Descompone el external_reference que viaja en la preferencia de Mercado Pago.
     * Formato: "{reservaId}" (anticipo, retrocompatible con lo ya emitido) o
     * "{reservaId}:{concepto}" (concepto = anticipo | saldo).
     *
     * @return array{0:int, 1:string} [reservaId, concepto]
     */
    public static function parseReferenciaExterna(string $referencia): array
    {
        $partes = explode(':', $referencia, 2);
        $reservaId = (int) ($partes[0] ?? 0);
        $concepto = isset($partes[1]) && $partes[1] !== '' ? $partes[1] : 'anticipo';

        return [$reservaId, in_array($concepto, ['anticipo', 'saldo'], true) ? $concepto : 'otro'];
    }

    /**
     * Registra un pago aprobado (Mercado Pago u otro medio futuro) en pagos_reserva y
     * recalcula reservas.monto_pagado como la suma de todos los pagos. Confirma la reserva si
     * seguia pendiente y lo pagado acumulado alcanza el anticipo esperado.
     *
     * Devuelve:
     *  - PAGO_CONFIRMO: esta llamada fue la que confirmo la reserva (el webhook manda el
     *    correo de confirmacion).
     *  - PAGO_REGISTRADO: el pago entro sobre una reserva ya confirmada (tipico del saldo);
     *    el webhook manda el aviso de "pago recibido".
     *  - PAGO_DUPLICADO: referencia_pago ya registrada (reintento de notificacion) o la
     *    reserva no existe; no hacer nada.
     *  - PAGO_INSUFICIENTE: pago nuevo pero lo acumulado no cubre el anticipo; la reserva
     *    queda "pendiente" (no hay estado intermedio en el esquema) con el pago igual
     *    registrado y una linea en el log para revision manual.
     *
     * Dedup por UNIQUE(referencia_pago): Mercado Pago reintenta la notificacion del mismo
     * pago varias veces, y con el modelo aditivo un doble conteo inflaria monto_pagado.
     *
     * Auditoria 2026-08-25, hallazgo SEG-02: se compara contra el anticipo real calculado
     * server-side, no contra lo que reporte Mercado Pago.
     */
    public function registrarPagoAprobado(int $reservaId, string $referenciaPago, float $montoPagado, string $concepto = 'otro'): string
    {
        return Database::transaction($this->db, function () use ($reservaId, $referenciaPago, $montoPagado, $concepto): string {
            $stmt = $this->db->prepare('SELECT estado, precio_total FROM reservas WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $reservaId]);
            $reserva = $stmt->fetch();

            if (!$reserva) {
                return self::PAGO_DUPLICADO;
            }

            $ins = $this->db->prepare(
                'INSERT IGNORE INTO pagos_reserva (reserva_id, referencia_pago, metodo_pago, concepto, monto)
                 VALUES (:rid, :ref, "mercadopago", :concepto, :monto)'
            );
            $ins->execute([
                'rid' => $reservaId,
                'ref' => $referenciaPago,
                'concepto' => in_array($concepto, ['anticipo', 'saldo'], true) ? $concepto : 'otro',
                'monto' => $montoPagado,
            ]);

            if ($ins->rowCount() === 0) {
                return self::PAGO_DUPLICADO;
            }

            $sum = $this->db->prepare('SELECT COALESCE(SUM(monto), 0) FROM pagos_reserva WHERE reserva_id = :id');
            $sum->execute(['id' => $reservaId]);
            $totalPagado = (float) $sum->fetchColumn();

            $this->db->prepare(
                'UPDATE reservas SET metodo_pago = "mercadopago", referencia_pago = :ref, monto_pagado = :monto WHERE id = :id'
            )->execute(['ref' => $referenciaPago, 'monto' => $totalPagado, 'id' => $reservaId]);

            if ($reserva['estado'] !== 'pendiente') {
                return self::PAGO_REGISTRADO;
            }

            $porcentajeAnticipo = max(1, min(100, (int) ConfiguracionSitio::get('porcentaje_anticipo_reserva', 100)));
            $anticipoEsperado = round(((float) $reserva['precio_total']) * $porcentajeAnticipo / 100, 2);

            if ($totalPagado + self::TOLERANCIA_MONTO_PAGO >= $anticipoEsperado) {
                $this->db->prepare(
                    'UPDATE reservas SET estado = "confirmada", confirmada_en = NOW() WHERE id = :id'
                )->execute(['id' => $reservaId]);

                return self::PAGO_CONFIRMO;
            }

            error_log(
                "[ReservaService] Pago {$referenciaPago} de la reserva {$reservaId}: total pagado {$totalPagado} "
                . "no alcanza el anticipo esperado ({$anticipoEsperado}). La reserva queda pendiente para revision manual."
            );

            return self::PAGO_INSUFICIENTE;
        });
    }

    /**
     * Usado por el cron: libera el cupo de todas las reservas pendientes vencidas.
     * Devuelve el listado de reservas expiradas (para notificar/loggear si se desea).
     */
    public function expirarVencidas(): array
    {
        $stmt = $this->db->query(
            'SELECT id, salida_id, num_personas FROM reservas WHERE estado = "pendiente" AND expira_en < NOW()'
        );
        $vencidas = $stmt->fetchAll();

        foreach ($vencidas as $reserva) {
            try {
                Database::transaction($this->db, function () use ($reserva): void {
                    $this->reponerCupo((int) $reserva['salida_id'], (int) $reserva['num_personas']);

                    $update = $this->db->prepare('UPDATE reservas SET estado = "expirada" WHERE id = :id AND estado = "pendiente"');
                    $update->execute(['id' => $reserva['id']]);
                });
            } catch (\Throwable $e) {
                // Una reserva con problemas no debe abortar el resto del lote del cron.
                error_log('[ReservaService] Error expirando reserva ' . $reserva['id'] . ': ' . $e->getMessage());
            }
        }

        return $vencidas;
    }

    private function reponerCupo(int $salidaId, int $personas): void
    {
        $stmtLock = $this->db->prepare('SELECT cupo_maximo, cupo_disponible FROM salidas WHERE id = :id FOR UPDATE');
        $stmtLock->execute(['id' => $salidaId]);
        $salida = $stmtLock->fetch();

        if (!$salida) {
            return;
        }

        $nuevoDisponible = min((int) $salida['cupo_maximo'], (int) $salida['cupo_disponible'] + $personas);

        $update = $this->db->prepare('UPDATE salidas SET cupo_disponible = :disp WHERE id = :id');
        $update->execute(['disp' => $nuevoDisponible, 'id' => $salidaId]);
    }

    private function precioBasePaquete(int $paqueteId): float
    {
        $stmt = $this->db->prepare('SELECT precio_desde FROM paquetes WHERE id = :id');
        $stmt->execute(['id' => $paqueteId]);

        return (float) $stmt->fetchColumn();
    }
}
