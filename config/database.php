<?php

declare(strict_types=1);

// Auditoria 2026-08-25, hallazgo CAL-06: DB_NAME/DB_USER sin default -- si un .env de
// produccion quedara incompleto, antes la app intentaba conectar en silencio con
// root/dreamgo/sin password (valores de desarrollo local) en vez de fallar con un mensaje
// claro, lo que convertia un .env mal armado en un error de conexion generico dificil de
// diagnosticar. host/port/password si mantienen default: son valores razonables aunque
// falten, y una contrasena vacia es normal para un MySQL/MariaDB local sin autenticacion.
if (($_ENV['APP_ENV'] ?? 'production') !== 'local') {
    foreach (['DB_NAME', 'DB_USER'] as $variable) {
        if (($_ENV[$variable] ?? '') === '') {
            throw new RuntimeException("Falta la variable de entorno {$variable} en .env (requerida fuera de APP_ENV=local).");
        }
    }
}

return [
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'dreamgo',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
];
