<?php

declare(strict_types=1);

/**
 * Bootstrap de la suite de integracion.
 *
 * A diferencia de la suite `unit` (100% en memoria, sin BD), estas pruebas necesitan una
 * base de datos MySQL/MariaDB real y DESECHABLE: cada test recrea/limpia tablas. Nunca
 * apuntes esto a la base de desarrollo.
 *
 * La app se conecta via `Core\Database`, que lee `config/database.php` -> `$_ENV`. Aqui se
 * fija `DB_NAME` a la base de pruebas ANTES de que se cargue nada, para que la conexion
 * singleton se abra ya apuntando ahi. `Dotenv::createImmutable()` (en config/config.php) no
 * pisa un valor ya presente en `$_ENV`, asi que este override gana sobre el `.env` real.
 *
 * Nombre de la base: `dreamgo_test` por defecto, o `DB_NAME_TEST` si esta definida en el
 * entorno. Credenciales: las mismas `DB_USER` / `DB_PASS` del `.env`.
 *
 *   mysql -u root -e "CREATE DATABASE dreamgo_test CHARACTER SET utf8mb4"
 *   composer test:integration
 */

$dbPruebas = getenv('DB_NAME_TEST') ?: 'dreamgo_test';

foreach (['APP_ENV' => 'local', 'DB_NAME' => $dbPruebas] as $clave => $valor) {
    $_ENV[$clave] = $valor;
    $_SERVER[$clave] = $valor;
    putenv("{$clave}={$valor}");
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';
