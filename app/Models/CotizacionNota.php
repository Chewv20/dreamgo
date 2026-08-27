<?php

namespace App\Models;

use Core\Auth;
use Core\Model;

class CotizacionNota extends Model
{
    protected static string $table = 'cotizacion_notas';

    /**
     * @return array<int, array<string, mixed>> notas de la cotizacion, de la mas reciente a la mas antigua
     */
    public static function porCotizacion(int $cotizacionId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM cotizacion_notas WHERE cotizacion_id = :id ORDER BY creado_en DESC, id DESC'
        );
        $stmt->execute(['id' => $cotizacionId]);

        return $stmt->fetchAll();
    }

    /** Agrega una nota atribuida al usuario admin en sesion. */
    public static function agregar(int $cotizacionId, string $nota): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO cotizacion_notas (cotizacion_id, usuario_id, usuario_nombre, nota)
             VALUES (:cotizacion_id, :usuario_id, :usuario_nombre, :nota)'
        );
        $stmt->execute([
            'cotizacion_id' => $cotizacionId,
            'usuario_id' => Auth::id(),
            'usuario_nombre' => Auth::nombre(),
            'nota' => $nota,
        ]);
    }
}
