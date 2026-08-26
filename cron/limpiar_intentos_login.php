<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Core\Database;

$db = Database::connection();

$stmtLogin = $db->prepare('DELETE FROM intentos_login WHERE creado_en < (NOW() - INTERVAL 30 DAY)');
$stmtLogin->execute();

// Auditoria 2026-08-25: intentos_accion (rate limiting de /reservar, /suscribir, /mi-reserva,
// /resena, ver App\Helpers\RateLimiter) es el mismo tipo de tabla de solo-crecimiento que
// intentos_login -- se purga aca mismo en vez de sumar un cron nuevo solo para esto.
$stmtAccion = $db->prepare('DELETE FROM intentos_accion WHERE creado_en < (NOW() - INTERVAL 30 DAY)');
$stmtAccion->execute();

cron_log(
    'limpiar_intentos_login',
    $stmtLogin->rowCount() . ' registro(s) de intentos de login purgado(s), '
    . $stmtAccion->rowCount() . ' registro(s) de intentos_accion purgado(s).'
);
