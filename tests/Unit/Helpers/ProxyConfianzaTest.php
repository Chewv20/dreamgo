<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ProxyConfianza;
use PHPUnit\Framework\TestCase;

/**
 * ProxyConfianza decide si se puede confiar en X-Forwarded-For / X-Forwarded-Proto. De el
 * dependen el rate-limiting (IP real del cliente) y el flag Secure de la cookie de sesion.
 * Auditoria 2026-08-31, hallazgo SEG-01.
 */
final class ProxyConfianzaTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['TRUSTED_PROXIES']);
    }

    public function testSinListaSeIgnoraElForwardedFor(): void
    {
        unset($_ENV['TRUSTED_PROXIES']);

        $server = [
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ];

        $this->assertSame('203.0.113.9', ProxyConfianza::ipCliente($server));
    }

    public function testConProxyConfiableSeTomaElClienteDelForwardedFor(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';

        $server = [
            'REMOTE_ADDR' => '10.9.9.9',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.23, 10.0.0.7',
        ];

        // 10.0.0.7 es proxy confiable -> se salta -> queda 198.51.100.23.
        $this->assertSame('198.51.100.23', ProxyConfianza::ipCliente($server));
    }

    public function testForwardedForSpoofeadoDesdeConexionDirectaNoCuenta(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';

        $server = [
            'REMOTE_ADDR' => '203.0.113.9', // conexion directa, no es proxy
            'HTTP_X_FORWARDED_FOR' => '1.1.1.1',
        ];

        $this->assertSame('203.0.113.9', ProxyConfianza::ipCliente($server));
    }

    public function testHttpsDirectoSiempreCuenta(): void
    {
        unset($_ENV['TRUSTED_PROXIES']);

        $this->assertTrue(ProxyConfianza::esHttps(['HTTPS' => 'on', 'REMOTE_ADDR' => '203.0.113.9']));
    }

    public function testForwardedProtoSoloCuentaTrasProxyConfiable(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.7', 'HTTP_X_FORWARDED_PROTO' => 'https'];

        $_ENV['TRUSTED_PROXIES'] = '';
        $this->assertFalse(ProxyConfianza::esHttps($server));

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8';
        $this->assertTrue(ProxyConfianza::esHttps($server));
    }

    public function testRangoCidrIpv6(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '2400:cb00::/32';

        $this->assertTrue(ProxyConfianza::esProxyConfiable('2400:cb00:0:1::5'));
        $this->assertFalse(ProxyConfianza::esProxyConfiable('2a00:1450::1'));
    }
}
