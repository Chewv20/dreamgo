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

// bitacora_admin (auditoria del panel, ver App\Helpers\Auditoria) tambien es de solo
// crecimiento; se conserva mas tiempo que los intentos porque es informacion de auditoria.
$stmtBitacora = $db->prepare('DELETE FROM bitacora_admin WHERE creado_en < (NOW() - INTERVAL 12 MONTH)');
$stmtBitacora->execute();

// Auditoria 2026-08-31, hallazgo PERF-03: log_correos_enviados (trazabilidad de correos, ver
// App\Services\MailerService) era la unica tabla de solo-crecimiento sin politica de
// retencion. 6 meses cubre de sobra cualquier revision de "se envio el correo X".
$stmtCorreos = $db->prepare('DELETE FROM log_correos_enviados WHERE enviado_en < (NOW() - INTERVAL 6 MONTH)');
$stmtCorreos->execute();

cron_log(
    'limpiar_intentos_login',
    $stmtLogin->rowCount() . ' registro(s) de intentos de login purgado(s), '
    . $stmtAccion->rowCount() . ' registro(s) de intentos_accion purgado(s), '
    . $stmtBitacora->rowCount() . ' registro(s) de bitacora_admin purgado(s), '
    . $stmtCorreos->rowCount() . ' registro(s) de log_correos_enviados purgado(s).'
);
