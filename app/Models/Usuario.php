<?php

namespace App\Models;

use Core\Model;

class Usuario extends Model
{
    protected static string $table = 'usuarios_admin';

    public static function conRol(): array
    {
        $stmt = self::db()->query(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios_admin u
             INNER JOIN roles r ON r.id = u.rol_id
             ORDER BY u.nombre ASC'
        );

        return $stmt->fetchAll();
    }

    public static function porEmail(string $email): array|false
    {
        return self::first(['email' => $email]);
    }

    /** true si el rol dado tiene es_sistema = 1 (rol Administrador, no eliminable/editable). */
    public static function esRolDeSistema(int $rolId): bool
    {
        $stmt = self::db()->prepare('SELECT es_sistema FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $rolId]);

        return (int) $stmt->fetchColumn() === 1;
    }

    /**
     * Cuenta usuarios activos cuyo rol es de sistema (Administrador). Con $excluyendoId se
     * ignora un usuario (el que se esta por editar) para responder "¿quedaria alguno mas?".
     */
    public static function contarAdminsSistemaActivos(?int $excluyendoId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM usuarios_admin u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE r.es_sistema = 1 AND u.activo = 1';
        $params = [];

        if ($excluyendoId !== null) {
            $sql .= ' AND u.id <> :excl';
            $params['excl'] = $excluyendoId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
