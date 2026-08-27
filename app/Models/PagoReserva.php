<?php

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
            'SELECT * FROM pagos_reserva WHERE reserva_id = :id ORDER BY creado_en DESC, id DESC'
        );
        $stmt->execute(['id' => $reservaId]);

        return $stmt->fetchAll();
    }
}
