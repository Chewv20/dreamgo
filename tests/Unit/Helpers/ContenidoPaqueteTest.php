<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ContenidoPaquete;
use PHPUnit\Framework\TestCase;

/**
 * Conversión HTML <-> campos estructurados del formulario de paquete. Lo importante: lo que
 * se puede representar va y vuelve sin cambios, y lo que no, devuelve null (el panel deja
 * ese campo en modo HTML) en vez de perder formato.
 */
final class ContenidoPaqueteTest extends TestCase
{
    public function testListaDesdeLineasIgnoraVaciasYViñetas(): void
    {
        $html = ContenidoPaquete::lineasALista("Vuelos\n\n- Hospedaje 3 noches\n• Traslados <aeropuerto>\n   ");
        $this->assertSame('<ul><li>Vuelos</li><li>Hospedaje 3 noches</li><li>Traslados &lt;aeropuerto&gt;</li></ul>', $html);
        $this->assertSame('', ContenidoPaquete::lineasALista(" \n "));
    }

    public function testListaIdaYVuelta(): void
    {
        $original = '<ul><li>Traslados aeropuerto-hotel-aeropuerto</li><li>3 noches de hospedaje todo incluido</li></ul>';
        $lineas = ContenidoPaquete::listaALineas($original);
        $this->assertSame("Traslados aeropuerto-hotel-aeropuerto\n3 noches de hospedaje todo incluido", $lineas);
        $this->assertSame($original, ContenidoPaquete::lineasALista($lineas));
    }

    public function testListaConFormatoNoSeConvierte(): void
    {
        $this->assertNull(ContenidoPaquete::listaALineas('<ul><li>Ver <a href="https://x.mx">mapa</a></li></ul>'));
        $this->assertNull(ContenidoPaquete::listaALineas('<p>Texto suelto</p>'));
        $this->assertNull(ContenidoPaquete::listaALineas('<ul><li>a</li></ul><ul><li>b</li></ul>'));
        $this->assertSame('', ContenidoPaquete::listaALineas(null));
    }

    public function testItinerarioDelSeedIdaYVuelta(): void
    {
        $original = '<p><strong>Día 1:</strong> Llegada y traslado al hotel. Tarde libre en la playa.</p>'
            . '<p><strong>Día 2:</strong> Excursión a Xcaret o cenote a elección.</p>';
        $dias = ContenidoPaquete::itinerarioADias($original);
        $this->assertSame([
            ['titulo' => 'Día 1', 'texto' => 'Llegada y traslado al hotel. Tarde libre en la playa.'],
            ['titulo' => 'Día 2', 'texto' => 'Excursión a Xcaret o cenote a elección.'],
        ], $dias);
        $this->assertSame($original, ContenidoPaquete::diasAItinerario($dias));
    }

    public function testItinerarioOmiteFilasVaciasYEscapa(): void
    {
        $html = ContenidoPaquete::diasAItinerario([
            ['titulo' => 'Días 1-3:', 'texto' => 'París & Roma'],
            ['titulo' => '', 'texto' => ''],
            ['titulo' => '', 'texto' => 'Regreso'],
        ]);
        $this->assertSame('<p><strong>Días 1-3:</strong> París &amp; Roma</p><p>Regreso</p>', $html);
    }

    public function testItinerarioConFormatoLibreNoSeConvierte(): void
    {
        $this->assertNull(ContenidoPaquete::itinerarioADias('<h3>Día 1</h3><p>Llegada</p>'));
        $this->assertNull(ContenidoPaquete::itinerarioADias('<p><strong>Día 1:</strong> Visita <em>opcional</em></p>'));
        $this->assertSame([], ContenidoPaquete::itinerarioADias(''));
    }

    public function testDescripcionIdaYVuelta(): void
    {
        $original = '<p>Disfruta de un escape perfecto.</p><p>Primera línea<br>Segunda línea</p>';
        $texto = ContenidoPaquete::parrafosATexto($original);
        $this->assertSame("Disfruta de un escape perfecto.\n\nPrimera línea\nSegunda línea", $texto);
        $this->assertSame($original, ContenidoPaquete::textoAParrafos($texto));
    }

    public function testDescripcionConNegritasNoSeConvierte(): void
    {
        $this->assertNull(ContenidoPaquete::parrafosATexto('<p>Hotel <strong>frente al mar</strong></p>'));
        $this->assertNull(ContenidoPaquete::parrafosATexto('<ul><li>a</li></ul>'));
    }

    public function testTextoAParrafosEscapaYNormalizaSaltos(): void
    {
        $this->assertSame('<p>a &lt;b&gt;</p><p>c</p>', ContenidoPaquete::textoAParrafos("a <b>\r\n\r\n\r\nc"));
        $this->assertSame('', ContenidoPaquete::textoAParrafos("  \n "));
    }
}
