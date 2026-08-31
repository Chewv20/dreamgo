<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Url;
use PHPUnit\Framework\TestCase;

/**
 * Url::segura() es la lista blanca de URLs que un administrador (roles contenido.gestionar /
 * configuracion.gestionar / articulos.gestionar) puede guardar para renderizarse luego en un
 * href. htmlspecialchars NO neutraliza javascript:, asi que este helper es la barrera real
 * contra XSS almacenado y open-redirect desde esos roles (auditoria 2026-08-27 M-02;
 * evasion con backslash: auditoria 2026-08-29 M4-03).
 */
final class UrlTest extends TestCase
{
    /**
     * @dataProvider urlsSeguras
     */
    public function testConservaUrlsDeLaListaBlanca(string $entrada): void
    {
        $this->assertSame($entrada, Url::segura($entrada));
    }

    public static function urlsSeguras(): array
    {
        return [
            ['https://ejemplo.com/ruta'],
            ['http://ejemplo.com'],
            ['/paquetes'],
            ['/destinos/cancun'],
            ['mailto:hola@ejemplo.com'],
            ['tel:+525512345678'],
            ['#seccion'],
        ];
    }

    /**
     * @dataProvider urlsPeligrosas
     */
    public function testDescartaUrlsFueraDeLaListaBlanca(string $entrada): void
    {
        $this->assertSame('', Url::segura($entrada));
    }

    public static function urlsPeligrosas(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript con espacio inicial' => [' javascript:alert(1)'],
            'javascript mayusculas' => ['JavaScript:alert(1)'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'protocolo relativo' => ['//evil.example'],
            'backslash protocolo relativo' => ['/\\evil.example'],
            'backslash doble' => ['\\\\evil.example'],
            'backslash intermedio' => ['/ruta\\..\\admin'],
            'salto de linea' => ["/ruta\njavascript:alert(1)"],
            'tab' => ["/\tevil"],
            'esquema raro' => ['ftp://ejemplo.com'],
            'vacio' => [''],
        ];
    }
}
