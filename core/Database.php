<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log('[DB] Error de conexion: ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$instance;
    }

    /**
     * Ejecuta $callback dentro de una transaccion: hace commit si termina bien, rollback y
     * relanza la excepcion si $callback lanza cualquier Throwable. Centraliza el patron
     * beginTransaction/try/commit/catch-rollBack que antes estaba repetido en varios
     * modelos y servicios.
     */
    public static function transaction(PDO $db, callable $callback): mixed
    {
        $db->beginTransaction();

        try {
            $resultado = $callback($db);
            $db->commit();

            return $resultado;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
