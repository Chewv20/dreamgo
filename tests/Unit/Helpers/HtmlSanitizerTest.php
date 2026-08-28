<?php

namespace Tests\Unit\Helpers;

use App\Helpers\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * HtmlSanitizer::limpiar() es la unica barrera entre el HTML WYSIWYG que edita el admin
 * (contenido del blog, itinerario/incluye/no_incluye/descripcion_larga de paquetes) y su
 * impresion SIN escapar en las vistas publicas. La CSP (script-src sin 'unsafe-inline') es un
 * backstop, pero este saneo es la defensa principal, asi que se cubre con una bateria de
 * payloads XSS conocidos (auditoria 2026-08-27, hallazgo B-01).
 */
final class HtmlSanitizerTest extends TestCase
{
    public function testDevuelveNullConEntradaVaciaOSoloEspacios(): void
    {
        $this->assertNull(HtmlSanitizer::limpiar(null));
        $this->assertNull(HtmlSanitizer::limpiar(''));
        $this->assertNull(HtmlSanitizer::limpiar('   '));
        $this->assertNull(HtmlSanitizer::limpiar('<script>alert(1)</script>'));
    }

    public function testEliminaScriptConSuContenido(): void
    {
        $this->assertNull(HtmlSanitizer::limpiar('<script>alert(1)</script>'));

        $limpio = HtmlSanitizer::limpiar('<p>Hola <script>alert(1)</script>mundo</p>');
        $this->assertStringNotContainsString('<script', (string) $limpio);
        $this->assertStringNotContainsString('alert(1)', (string) $limpio);
        $this->assertStringContainsString('Hola', (string) $limpio);
        $this->assertStringContainsString('mundo', (string) $limpio);
    }

    public function testEliminaStyleConSuContenido(): void
    {
        $limpio = HtmlSanitizer::limpiar('<p><style>body{display:none}</style>texto</p>');
        $this->assertStringNotContainsString('<style', (string) $limpio);
        $this->assertStringNotContainsString('display:none', (string) $limpio);
        $this->assertStringContainsString('texto', (string) $limpio);
    }

    public function testQuitaTodosLosAtributosDeEtiquetasPermitidas(): void
    {
        $limpio = (string) HtmlSanitizer::limpiar('<p onclick="alert(1)" style="color:red" class="x">hola</p>');
        $this->assertStringNotContainsString('onclick', $limpio);
        $this->assertStringNotContainsString('style', $limpio);
        $this->assertStringNotContainsString('class', $limpio);
        $this->assertStringContainsString('hola', $limpio);
        $this->assertStringContainsString('<p>', $limpio);
    }

    public function testEtiquetaNoPermitidaSeDesenvuelveConservandoElTexto(): void
    {
        $limpio = (string) HtmlSanitizer::limpiar('<div><table><tr><td>celda</td></tr></table></div>');
        $this->assertStringNotContainsString('<div', $limpio);
        $this->assertStringNotContainsString('<table', $limpio);
        $this->assertStringNotContainsString('<td', $limpio);
        $this->assertStringContainsString('celda', $limpio);
    }

    public function testImgConOnerrorSeElimina(): void
    {
        $limpio = HtmlSanitizer::limpiar('<img src=x onerror=alert(1)>');
        $this->assertStringNotContainsString('onerror', (string) $limpio);
        $this->assertStringNotContainsString('<img', (string) $limpio);
    }

    public function testConservaEtiquetasDeFormatoPermitidas(): void
    {
        $limpio = (string) HtmlSanitizer::limpiar('<p>Un <strong>texto</strong> con <em>enfasis</em> y <b>negrita</b>.</p>');
        $this->assertStringContainsString('<strong>', $limpio);
        $this->assertStringContainsString('<em>', $limpio);
        $this->assertStringContainsString('<b>', $limpio);
    }

    public function testConservaListas(): void
    {
        $limpio = (string) HtmlSanitizer::limpiar('<ul><li>uno</li><li>dos</li></ul>');
        $this->assertStringContainsString('<ul>', $limpio);
        $this->assertStringContainsString('<li>uno</li>', $limpio);
        $this->assertStringContainsString('<li>dos</li>', $limpio);
    }

    public function testEnlaceConJavascriptSchemeSeNeutralizaAAnclaVacia(): void
    {
        $limpio = (string) HtmlSanitizer::limpiar('<a href="javascript:alert(1)">click</a>');
        $this->assertStringNotContainsString('javascript:', $limpio);
        $this->assertStringContainsString('href="#"', $limpio);
        $this->assertStringContainsString('rel="noopener"', $limpio);
        $this->assertStringContainsString('click', $limpio);
    }

    public function testEnlaceConEsquemasRarosSeNeutraliza(): void
    {
        foreach (['data:text/html,<x>', 'vbscript:msgbox(1)', ' javascript:alert(1)', 'JAVASCRIPT:alert(1)'] as $href) {
            $limpio = (string) HtmlSanitizer::limpiar('<a href="' . $href . '">x</a>');
            $this->assertStringContainsString('href="#"', $limpio, "href={$href} deberia quedar en #");
        }
    }

    public function testEnlaceProtocoloRelativoSeNeutraliza(): void
    {
        // B-03: //host es protocolo-relativo (apunta afuera del sitio); no debe pasar como
        // enlace "interno".
        $limpio = (string) HtmlSanitizer::limpiar('<a href="//evil.example">x</a>');
        $this->assertStringNotContainsString('//evil.example', $limpio);
        $this->assertStringContainsString('href="#"', $limpio);
    }

    public function testEnlaceHttpsYRelativoYMailtoSeConservan(): void
    {
        $https = (string) HtmlSanitizer::limpiar('<a href="https://ejemplo.com/ruta">x</a>');
        $this->assertStringContainsString('href="https://ejemplo.com/ruta"', $https);

        $relativo = (string) HtmlSanitizer::limpiar('<a href="/paquetes">x</a>');
        $this->assertStringContainsString('href="/paquetes"', $relativo);

        $mailto = (string) HtmlSanitizer::limpiar('<a href="mailto:a@b.com">x</a>');
        $this->assertStringContainsString('href="mailto:a@b.com"', $mailto);
    }

    public function testNoDejaEstadoGlobalDeLibxmlActivado(): void
    {
        // B-02: limpiar() debe restaurar libxml_use_internal_errors a su valor previo.
        $previo = libxml_use_internal_errors(false);
        libxml_use_internal_errors($previo);

        HtmlSanitizer::limpiar('<p>algo <b>roto</b></p>');

        $estadoActual = libxml_use_internal_errors(false);
        libxml_use_internal_errors($estadoActual);
        $this->assertFalse($estadoActual, 'limpiar() dejo libxml_use_internal_errors en true');
    }
}
