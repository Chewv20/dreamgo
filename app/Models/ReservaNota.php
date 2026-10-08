<?php

declare(strict_types=1);

namespace App\Models;

use Core\Auth;
use Core\Model;

/** Notas internas de seguimiento de una reserva (mismo diseño que CotizacionNota). */
class ReservaNota extends Model
{
    protected static string $table = 'reserva_notas';

    /**
     * @return array<int, array<string, mixed>> notas de la reserva, de la más reciente a la más antigua
     */
    public static function porReserva(int $reservaId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM reserva_notas WHERE reserva_id = :id ORDER BY creado_en DESC, id DESC'
        );
        $stmt->execute(['id' => $reservaId]);

        return $stmt->fetchAll();
    }

    /** Agrega una nota atribuida al usuario admin en sesión. */
    public static function agregar(int $reservaId, string $nota): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO reserva_notas (reserva_id, usuario_id, usuario_nombre, nota)
             VALUES (:reserva_id, :usuario_id, :usuario_nombre, :nota)'
        );
        $stmt->execute([
            'reserva_id' => $reservaId,
            'usuario_id' => Auth::id(),
            'usuario_nombre' => Auth::nombre(),
            'nota' => $nota,
        ]);
    }
}
