<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Categoria extends Model
{
    protected static string $table = 'categorias';

    public static function activas(): array
    {
        return self::where(['activo' => 1], 'orden ASC');
    }

    public static function porSlug(string $slug): array|false
    {
        return self::first(['slug' => $slug]);
    }

    /**
     * Listado para el panel: todos los destinos (activos e inactivos) en su orden de
     * presentacion, con el numero de paquetes que dependen de cada uno (para avisar antes de
     * eliminar y para la columna del listado).
     */
    public static function adminListado(): array
    {
        return self::db()->query(
            'SELECT c.*, COUNT(p.id) AS total_paquetes
             FROM categorias c
             LEFT JOIN paquetes p ON p.categoria_id = c.id
             GROUP BY c.id
             ORDER BY c.orden ASC, c.nombre ASC'
        )->fetchAll();
    }

    /** true si algun paquete apunta a este destino (FK ON DELETE RESTRICT: no se puede borrar). */
    public static function tienePaquetes(int $id): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM paquetes WHERE categoria_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    /** Siguiente valor de `orden` para que un destino nuevo quede al final de la lista. */
    public static function siguienteOrden(): int
    {
        return (int) self::db()->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM categorias')->fetchColumn();
    }
}
