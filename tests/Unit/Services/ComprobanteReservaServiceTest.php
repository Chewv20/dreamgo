<?php

namespace Tests\Unit\Services;

use App\Models\ConfiguracionSitio;
use App\Services\ComprobanteReservaService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Comprueba que el comprobante se genera como un PDF valido a partir del array que devuelve
 * Reserva::conDetalle(), sin tocar la base de datos: se precarga la cache de
 * ConfiguracionSitio por reflexion para que get() no intente conectar.
 */
final class ComprobanteReservaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 3));
        }

        $cache = new ReflectionProperty(ConfiguracionSitio::class, 'cache');
        $cache->setValue(null, [
            'direccion' => 'Calle Falsa 123, CDMX',
            'telefono_contacto' => '55 5555 5555',
            'email_contacto' => 'contacto@example.com',
            'whatsapp_numero' => '5215555555555',
        ]);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(ConfiguracionSitio::class, 'cache'))->setValue(null, null);
    }

    private function reservaFixture(): array
    {
        return [
            'id' => 42,
            'codigo_reserva' => 'DG-2026-000042',
            'token_publico' => str_repeat('a', 32),
            'num_personas' => 2,
            'precio_total' => '10000.00',
            'monto_pagado' => '3000.00',
            'estado' => 'confirmada',
            'expira_en' => null,
            'confirmada_en' => '2026-08-20 10:00:00',
            'cancelada_en' => null,
            'metodo_pago' => 'mercadopago',
            'referencia_pago' => '123456789',
            'creado_en' => '2026-08-18 09:00:00',
            'cliente_nombre' => 'Ana López',
            'cliente_email' => 'ana@example.com',
            'cliente_telefono' => '5544332211',
            'fecha_salida' => '2026-09-15',
            'fecha_regreso' => '2026-09-20',
            'paquete_titulo' => 'Cañón del Sumidero',
            'paquete_moneda' => 'MXN',
        ];
    }

    public function testGeneraUnPdfNoVacio(): void
    {
        $pdf = (new ComprobanteReservaService())->generarPdf($this->reservaFixture());

        $this->assertNotSame('', $pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function testFuncionaAunSinDatosDeContactoDeLaAgencia(): void
    {
        (new ReflectionProperty(ConfiguracionSitio::class, 'cache'))->setValue(null, []);

        $pdf = (new ComprobanteReservaService())->generarPdf($this->reservaFixture());

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function testNombreArchivoUsaElCodigoDeReserva(): void
    {
        $nombre = (new ComprobanteReservaService())->nombreArchivo($this->reservaFixture());

        $this->assertSame('comprobante-DG-2026-000042.pdf', $nombre);
    }
}
