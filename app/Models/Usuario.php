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
}
