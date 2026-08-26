<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL_PATH')) {
    $scriptDir = PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_NAME'])
        ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']))
        : '/';
    define('BASE_URL_PATH', $scriptDir === '/' ? '' : rtrim($scriptDir, '/'));
}

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

$appEnv = $_ENV['APP_ENV'] ?? 'production';

if ($appEnv === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    $rutaErrorLog = BASE_PATH . '/storage/logs/php-error.log';

    // Auditoria 2026-08-25, hallazgo CFG-03: mismo problema y misma solucion minima que
    // cron.log (ver cron/_bootstrap.php) -- PHP solo hace append via log_errors, nunca rota
    // por si solo. Se revisa en cada request (un stat() es barato) para no depender de un
    // cron aparte.
    if (is_file($rutaErrorLog) && filesize($rutaErrorLog) >= 5 * 1024 * 1024) {
        rename($rutaErrorLog, $rutaErrorLog . '.1');
    }

    ini_set('error_log', $rutaErrorLog);
}

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // style-src necesita 'unsafe-inline' porque las vistas usan style="" inline en varios
    // lugares (ver AUDITORIA.md); script-src NO lo tiene, todo el JS ya vive en
    // public/assets/js/. No hay CDNs externos (fuentes, JS, CSS son todos same-origin).
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "font-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'"
    );
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // $_SERVER['HTTPS'] solo lo pone el servidor web cuando PHP mismo termina el TLS. Si en
    // algun momento el sitio queda detras de un proxy/balanceador que termina el TLS antes
    // (Cloudflare, un load balancer, etc.), PHP ve trafico plano y esta variable nunca es
    // 'on' aunque el visitante si este en HTTPS - la cookie de sesion perderia el flag
    // Secure sin que nadie lo note. X-Forwarded-Proto es el header estandar que ponen esos
    // proxies para avisar el esquema original; se usa como respaldo.
    $isHttps = ($_SERVER['HTTPS'] ?? '') === 'on'
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
