<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Resuelve la IP real del cliente y el esquema (http/https) de la peticion original cuando el
 * sitio esta detras de un proxy que termina el TLS (Cloudflare, un balanceador).
 *
 * Auditoria 2026-08-31, hallazgo SEG-01: por defecto NO se confia en ningun header reenviado
 * (`REMOTE_ADDR` a secas), que es lo correcto para el despliegue documentado en Hostinger sin
 * proxy. Si se declara `TRUSTED_PROXIES` en el `.env` (lista separada por comas de IPs o
 * rangos CIDR), y la conexion entrante viene de uno de esos rangos, entonces se toma:
 *   - la IP del cliente de `X-Forwarded-For` (la ultima que NO sea a su vez un proxy confiable),
 *   - el esquema de `X-Forwarded-Proto`.
 *
 * Sin esta lista, un cliente directo podria mandar `X-Forwarded-For` / `X-Forwarded-Proto`
 * arbitrarios y falsear su IP (evadir / envenenar el rate-limiting) o el flag Secure de la
 * cookie de sesion.
 */
final class ProxyConfianza
{
    /** @return list<string> rangos declarados en TRUSTED_PROXIES (vacio = no hay proxy) */
    private static function rangosConfiables(): array
    {
        $crudo = trim((string) ($_ENV['TRUSTED_PROXIES'] ?? ''));
        if ($crudo === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $crudo)), static fn ($r) => $r !== ''));
    }

    public static function esProxyConfiable(string $ip): bool
    {
        foreach (self::rangosConfiables() as $rango) {
            if (self::ipEnRango($ip, $rango)) {
                return true;
            }
        }

        return false;
    }

    /**
     * IP real del cliente. Si `REMOTE_ADDR` no es un proxy confiable (caso por defecto), se
     * devuelve tal cual. Si lo es, se recorre `X-Forwarded-For` de derecha a izquierda y se
     * devuelve la primera IP que no sea otro proxy confiable.
     *
     * @param array<string, mixed> $server normalmente $_SERVER
     */
    public static function ipCliente(array $server): string
    {
        $remoto = (string) ($server['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($remoto === '' || !self::esProxyConfiable($remoto)) {
            return $remoto === '' ? '0.0.0.0' : $remoto;
        }

        $cadena = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($cadena === '') {
            return $remoto;
        }

        $ips = array_map('trim', explode(',', $cadena));
        for ($i = count($ips) - 1; $i >= 0; $i--) {
            $ip = $ips[$i];
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false && !self::esProxyConfiable($ip)) {
                return $ip;
            }
        }

        return $remoto;
    }

    /**
     * ¿La peticion original venia por HTTPS? Se mira `X-Forwarded-Proto` solo si `REMOTE_ADDR`
     * es un proxy confiable; en caso contrario solo cuenta `$_SERVER['HTTPS']`.
     *
     * @param array<string, mixed> $server normalmente $_SERVER
     */
    public static function esHttps(array $server): bool
    {
        if (($server['HTTPS'] ?? '') === 'on') {
            return true;
        }

        $remoto = (string) ($server['REMOTE_ADDR'] ?? '');

        return $remoto !== ''
            && self::esProxyConfiable($remoto)
            && strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /** Coincidencia de $ip contra una IP suelta o un rango CIDR (IPv4 o IPv6). */
    private static function ipEnRango(string $ip, string $rango): bool
    {
        if (!str_contains($rango, '/')) {
            return $ip === $rango;
        }

        [$subred, $bits] = explode('/', $rango, 2);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subredBin = @inet_pton($subred);
        if ($ipBin === false || $subredBin === false || strlen($ipBin) !== strlen($subredBin)) {
            return false;
        }

        $bytesEnteros = intdiv($bits, 8);
        $bitsSueltos = $bits % 8;

        if ($bytesEnteros > 0 && strncmp($ipBin, $subredBin, $bytesEnteros) !== 0) {
            return false;
        }

        if ($bitsSueltos === 0) {
            return true;
        }

        $mascara = chr((0xFF << (8 - $bitsSueltos)) & 0xFF);

        return (ord($ipBin[$bytesEnteros]) & ord($mascara)) === (ord($subredBin[$bytesEnteros]) & ord($mascara));
    }
}
