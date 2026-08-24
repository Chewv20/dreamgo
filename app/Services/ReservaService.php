<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ConfiguracionSitio;
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
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @param array{salida_id:int, nombre:string, email:string, telefono:string, num_personas:int} $datos
     * @return int id de la reserva creada
     */
    public function crear(array $datos): int
    {
        return Database::transaction($this->db, function () use ($datos): int {
            $stmtSalida = $this->db->prepare('SELECT * FROM salidas WHERE id = :id FOR UPDATE');
            $stmtSalida->execute(['id' => $datos['salida_id']]);
            $salida = $stmtSalida->fetch();

            if (!$salida) {
                throw new RuntimeException('La fecha de salida no existe.');
            }

            if ($salida['estado'] !== 'abierta') {
                throw new RuntimeException('Esta fecha de salida ya no esta disponible.');
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

            $stmtInsert = $this->db->prepare(
                'INSERT INTO reservas (codigo_reserva, salida_id, cliente_id, num_personas, codigo_descuento_id, precio_total, estado, expira_en)
                 VALUES (:codigo, :salida_id, :cliente_id, :num_personas, :descuento_id, :precio_total, "pendiente", DATE_ADD(NOW(), INTERVAL :horas HOUR))'
            );
            $stmtInsert->execute([
                'codigo' => 'TEMP',
                'salida_id' => $salida['id'],
                'cliente_id' => $clienteId,
                'num_personas' => $datos['num_personas'],
                'descuento_id' => $descuentoId,
                'precio_total' => $precioTotal,
                'horas' => $horasExpiracion,
            ]);

            $reservaId = (int) $this->db->lastInsertId();
            $codigo = 'DG-' . date('Y') . '-' . str_pad((string) $reservaId, 6, '0', STR_PAD_LEFT);

            $stmtCodigo = $this->db->prepare('UPDATE reservas SET codigo_reserva = :codigo WHERE id = :id');
            $stmtCodigo->execute(['codigo' => $codigo, 'id' => $reservaId]);

            return $reservaId;
        });
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
