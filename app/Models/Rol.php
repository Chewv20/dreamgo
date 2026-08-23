<?php

namespace App\Models;

use Core\Model;

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

    public static function permisosDelRol(int $rolId): array
    {
        $stmt = self::db()->prepare(
            'SELECT permiso_id FROM rol_permiso WHERE rol_id = :rol_id'
        );
        $stmt->execute(['rol_id' => $rolId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'permiso_id'));
    }

    /**
     * @param int[] $permisoIds
     */
    public static function sincronizarPermisos(int $rolId, array $permisoIds): void
    {
        $db = self::db();
        $db->beginTransaction();

        try {
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

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
