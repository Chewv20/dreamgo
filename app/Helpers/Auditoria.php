<?php

namespace App\Helpers;

use Core\Auth;
use Core\Database;

/**
 * Registra una accion sensible del panel en bitacora_admin. Llamar despues de que la accion
 * se completo con exito.
 *
 * Nunca lanza: si el INSERT falla, se deja una linea en el log de errores y la accion
 * auditada sigue su curso (una bitacora rota no debe tumbar una confirmacion de reserva).
 */
final class Auditoria
{
    public static function registrar(
        string $accion,
        ?string $entidadTipo = null,
        ?int $entidadId = null,
        ?string $detalle = null
    ): void {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO bitacora_admin (usuario_id, usuario_nombre, accion, entidad_tipo, entidad_id, detalle, ip)
                 VALUES (:usuario_id, :usuario_nombre, :accion, :entidad_tipo, :entidad_id, :detalle, :ip)'
            );
            $stmt->execute([
                'usuario_id' => Auth::id(),
                'usuario_nombre' => Auth::nombre(),
                'accion' => mb_substr($accion, 0, 50),
                'entidad_tipo' => $entidadTipo !== null ? mb_substr($entidadTipo, 0, 30) : null,
                'entidad_id' => $entidadId,
                'detalle' => $detalle !== null ? mb_substr($detalle, 0, 500) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('[Auditoria] No se pudo registrar "' . $accion . '": ' . $e->getMessage());
        }
    }
}
