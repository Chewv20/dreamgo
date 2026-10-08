<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class PagoReserva extends Model
{
    protected static string $table = 'pagos_reserva';

    /**
     * Historial de pagos de una reserva, del mas reciente al mas antiguo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function historialDeReserva(int $reservaId): array
    {
        $stmt = self::db()->prepare(
            'SELECT pr.*, u.nombre AS registrado_por_nombre
             FROM pagos_reserva pr
             LEFT JOIN usuarios_admin u ON u.id = pr.registrado_por
             WHERE pr.reserva_id = :id
             ORDER BY pr.creado_en DESC, pr.id DESC'
        );
        $stmt->execute(['id' => $reservaId]);

        return $stmt->fetchAll();
    }
}
