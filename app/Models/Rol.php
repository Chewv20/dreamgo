<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use PDO;

class Rol extends Model
{
    protected static string $table = 'roles';

    public static function conConteoUsuarios(): array
    {
        $stmt = self::db()->query(
            'SELECT r.*, COUNT(u.id) AS total_usuarios
             FROM roles r
             LEFT JOIN usuarios_admin u ON u.rol_id = r.id
             GROUP BY r.id
             ORDER BY r.id ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * @param int[] $rolIds
     * @return array<int, int[]> permiso_id[] indexado por rol_id
     */
    public static function permisosPorRoles(array $rolIds): array
    {
        if ($rolIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rolIds), '?'));
        $stmt = self::db()->prepare(
            "SELECT rol_id, permiso_id FROM rol_permiso WHERE rol_id IN ({$placeholders})"
        );
        $stmt->execute(array_values($rolIds));

        $porRol = [];
        foreach ($stmt->fetchAll() as $fila) {
            $porRol[(int) $fila['rol_id']][] = (int) $fila['permiso_id'];
        }

        return $porRol;
    }

    /**
     * @param int[] $permisoIds
     */
    public static function sincronizarPermisos(int $rolId, array $permisoIds): void
    {
        self::transaction(function (PDO $db) use ($rolId, $permisoIds): void {
            $delete = $db->prepare('DELETE FROM rol_permiso WHERE rol_id = :rol_id');
            $delete->execute(['rol_id' => $rolId]);

            if ($permisoIds !== []) {
                $insert = $db->prepare('INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (:rol_id, :permiso_id)');
                foreach (array_unique($permisoIds) as $permisoId) {
                    $insert->execute(['rol_id' => $rolId, 'permiso_id' => $permisoId]);
                }
            }

            $marcarActualizado = $db->prepare('UPDATE roles SET permisos_actualizado_en = NOW() WHERE id = :rol_id');
            $marcarActualizado->execute(['rol_id' => $rolId]);
        });
    }
}
