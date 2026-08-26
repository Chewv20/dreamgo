<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';

/**
 * Auditoria 2026-08-25, hallazgo CFG-03: cron.log crecia sin limite (FILE_APPEND puro, sin
 * ningun mecanismo de purga). Rotacion minima de una sola generacion -- no hace falta mas
 * para el volumen de este sitio: si el archivo actual supera 5MB, se conserva como
 * cron.log.1 (pisando la rotacion anterior) y se empieza uno nuevo.
 */
function cron_rotar_log_si_crecio(string $ruta, int $maxBytes = 5 * 1024 * 1024): void
{
    if (is_file($ruta) && filesize($ruta) >= $maxBytes) {
        rename($ruta, $ruta . '.1');
    }
}

function cron_log(string $script, string $mensaje): void
{
    $ruta = BASE_PATH . '/storage/logs/cron.log';
    cron_rotar_log_si_crecio($ruta);

    $linea = sprintf('[%s] [%s] %s' . PHP_EOL, date('Y-m-d H:i:s'), $script, $mensaje);
    file_put_contents($ruta, $linea, FILE_APPEND);
    echo $linea;
}

function cron_nombre_script(): string
{
    return basename($_SERVER['argv'][0] ?? $_SERVER['SCRIPT_NAME'] ?? 'desconocido', '.php');
}

// Red de seguridad para los scripts de cron: sin esto, una excepcion sin capturar (ej. un
// PDOException) o un error fatal de PHP terminan el proceso sin dejar rastro en cron.log,
// como si el cron nunca se hubiera ejecutado. No hace falta try/catch en cada script
// individual, esto cubre a todos los que hagan `require __DIR__ . '/_bootstrap.php';`.
set_exception_handler(static function (Throwable $e): void {
    cron_log(cron_nombre_script(), 'ERROR no capturado: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    exit(1);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    cron_log(cron_nombre_script(), "ERROR fatal: {$error['message']} en {$error['file']}:{$error['line']}");
});
