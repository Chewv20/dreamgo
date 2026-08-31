<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class IntentoLogin extends Model
{
    protected static string $table = 'intentos_login';

    public static function registrar(string $email, string $ip, bool $exitoso): void
    {
        static::insert([
            'email' => $email,
            'ip' => $ip,
            'exitoso' => $exitoso ? 1 : 0,
        ]);
    }

    public static function fallidosPorEmail(string $email, int $minutos): int
    {
        return self::contarFallidos('email', $email, $minutos);
    }

    public static function fallidosPorIp(string $ip, int $minutos): int
    {
        return self::contarFallidos('ip', $ip, $minutos);
    }

    /**
     * El limite de tiempo se calcula con NOW() del propio MySQL (no en PHP)
     * para evitar desfases si el servidor web y la base de datos corren en
     * zonas horarias distintas.
     */
    private static function contarFallidos(string $columna, string $valor, int $minutos): int
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM intentos_login
             WHERE {$columna} = :valor AND exitoso = 0
             AND creado_en >= (NOW() - INTERVAL {$minutos} MINUTE)"
        );
        $stmt->execute(['valor' => $valor]);

        return (int) $stmt->fetchColumn();
    }
}
