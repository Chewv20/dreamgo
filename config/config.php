<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Nonce por peticion para los <script> inline propios (bootstrap de GA4 / Meta Pixel y los
// eventos de conversion en las paginas de "gracias"). Va en la CSP mas abajo. Se define
// siempre (tambien en CLI, donde es inofensivo) para que las vistas puedan referenciarlo
// sin comprobar defined().
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', bin2hex(random_bytes(16)));
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
    // script-src y style-src: 'self' + un nonce por peticion (CSP_NONCE) para los pocos
    // <style>/<script> inline propios (colores del sitio y colores de bloque en las vistas
    // publicas; bootstrap de analitica y eventos de conversion). NINGUNO lleva 'unsafe-inline':
    // no queda ningun style="" atributo inline en las vistas (bloque 3b, 2026). Los hosts de
    // googletagmanager/facebook estan permitidos para GA4 / Meta Pixel; solo se cargan si estan
    // las vars GA4_MEASUREMENT_ID / META_PIXEL_ID en .env y el visitante acepta el banner de
    // cookies (ver App\Helpers\Analytics y AUDITORIA.md, seguimiento 2026-08-27).
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'nonce-" . CSP_NONCE . "' https://www.googletagmanager.com https://connect.facebook.net; "
        . "style-src 'self' 'nonce-" . CSP_NONCE . "'; "
        . "img-src 'self' data: https://www.googletagmanager.com https://*.google-analytics.com https://www.facebook.com; "
        . "font-src 'self'; "
        . "connect-src 'self' https://www.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com https://connect.facebook.net https://www.facebook.com; "
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
