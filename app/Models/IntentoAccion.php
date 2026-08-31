<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class IntentoAccion extends Model
{
    protected static string $table = 'intentos_accion';

    public static function registrar(string $accion, ?string $identificador, string $ip): void
    {
        static::insert([
            'accion' => $accion,
            'identificador' => $identificador,
            'ip' => $ip,
        ]);
    }

    public static function contarPorIdentificador(string $accion, string $identificador, int $minutos): int
    {
        return self::contar('identificador', $accion, $identificador, $minutos);
    }

    public static function contarPorIp(string $accion, string $ip, int $minutos): int
    {
        return self::contar('ip', $accion, $ip, $minutos);
    }

    /**
     * El limite de tiempo se calcula con NOW() del propio MySQL (no en PHP), mismo motivo
     * que IntentoLogin: evita desfases si el servidor web y la base de datos corren en
     * zonas horarias distintas.
     */
    private static function contar(string $columna, string $accion, string $valor, int $minutos): int
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM intentos_accion
             WHERE accion = :accion AND {$columna} = :valor
             AND creado_en >= (NOW() - INTERVAL {$minutos} MINUTE)"
        );
        $stmt->execute(['accion' => $accion, 'valor' => $valor]);

        return (int) $stmt->fetchColumn();
    }
}
