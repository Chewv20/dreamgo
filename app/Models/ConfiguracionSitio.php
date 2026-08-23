<?php

namespace App\Models;

use Core\Database;

class ConfiguracionSitio
{
    private static ?array $cache = null;

    public static function get(string $clave, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::cargar();
        }

        return self::$cache[$clave] ?? $default;
    }

    public static function set(string $clave, string $valor): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracion_sitio (clave, valor) VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = :valor_actualizado'
        );
        $stmt->execute(['clave' => $clave, 'valor' => $valor, 'valor_actualizado' => $valor]);

        self::$cache = null;
    }

    private static function cargar(): void
    {
        $stmt = Database::connection()->query('SELECT clave, valor FROM configuracion_sitio');
        self::$cache = array_column($stmt->fetchAll(), 'valor', 'clave');
    }
}
