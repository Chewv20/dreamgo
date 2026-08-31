<?php

namespace Tests\Unit\Core;

use Core\Router;
use PHPUnit\Framework\TestCase;

/**
 * Router::add() construye la regex de cada ruta. Se cubre el armado (auditoria 2026-08-27,
 * informativo): los tramos literales van con preg_quote y solo {param} pasa a grupo de
 * captura, para que un metacaracter de PCRE en una ruta no altere el matching.
 */
final class RouterTest extends TestCase
{
    /** @return list<array{pattern: string, regex: string}> */
    private function rutas(Router $router): array
    {
        $prop = (new \ReflectionClass($router))->getProperty('routes');
        $prop->setAccessible(true);

        return $prop->getValue($router);
    }

    private function regexDe(Router $router, string $pattern): string
    {
        foreach ($this->rutas($router) as $ruta) {
            if ($ruta['pattern'] === $pattern) {
                return $ruta['regex'];
            }
        }

        $this->fail("Ruta no registrada: {$pattern}");
    }

    public function testRutaSimpleGeneraLaMismaRegexQueAntes(): void
    {
        $r = new Router();
        $r->get('/paquetes/{slug}', ['C', 'm']);

        $this->assertSame('#^/paquetes/([^/]+)$#', $this->regexDe($r, '/paquetes/{slug}'));
    }

    public function testVariosParametrosEnUnaRuta(): void
    {
        $r = new Router();
        $r->get('/paquetes/{slug}/reservar/{salidaId}', ['C', 'm']);

        $regex = $this->regexDe($r, '/paquetes/{slug}/reservar/{salidaId}');
        $this->assertSame(1, preg_match($regex, '/paquetes/cancun/reservar/42'));
        $this->assertSame(0, preg_match($regex, '/paquetes/cancun/reservar/42/extra'));
    }

    public function testMatchYNoMatchBasicos(): void
    {
        $r = new Router();
        $r->get('/blog/{slug}', ['C', 'm']);
        $regex = $this->regexDe($r, '/blog/{slug}');

        $this->assertSame(1, preg_match($regex, '/blog/mi-primer-post'));
        $this->assertSame(0, preg_match($regex, '/blog/mi-post/comentarios'));
        $this->assertSame(0, preg_match($regex, '/blogX/mi-post'));
    }

    public function testMetacaracteresEnLaRutaSeTratanComoLiterales(): void
    {
        $r = new Router();
        // Ruta hipotetica con caracteres que en PCRE significan algo (. + ()).
        $r->get('/descargas/archivo.v1+final/{id}', ['C', 'm']);
        $regex = $this->regexDe($r, '/descargas/archivo.v1+final/{id}');

        $this->assertSame(1, preg_match($regex, '/descargas/archivo.v1+final/7'));
        // El '.' no debe actuar como comodin, ni el '+' como cuantificador.
        $this->assertSame(0, preg_match($regex, '/descargas/archivoXv1+final/7'));
        $this->assertSame(0, preg_match($regex, '/descargas/archivo.v1final/7'));
    }
}
