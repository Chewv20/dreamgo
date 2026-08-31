<?php

namespace Tests\Unit\Core;

use Core\Auth;
use PHPUnit\Framework\TestCase;

/**
 * Auth::sesionCaducada() cierra la sesion admin por inactividad (2 h) o por duracion absoluta
 * (12 h). Auditoria 2026-08-31, hallazgo SEG-06.
 */
final class AuthSesionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSesionRecienActivaNoCaduca(): void
    {
        $_SESSION['admin_inicio_sesion'] = time() - 60;
        $_SESSION['admin_ultima_actividad'] = time() - 60;

        $this->assertFalse(Auth::sesionCaducada());
    }

    public function testCaducaPorInactividad(): void
    {
        $_SESSION['admin_inicio_sesion'] = time() - 8000;
        $_SESSION['admin_ultima_actividad'] = time() - 7300; // > 2 h

        $this->assertTrue(Auth::sesionCaducada());
    }

    public function testCaducaPorDuracionAbsolutaAunConActividadReciente(): void
    {
        $_SESSION['admin_inicio_sesion'] = time() - 43_500; // > 12 h
        $_SESSION['admin_ultima_actividad'] = time() - 10;

        $this->assertTrue(Auth::sesionCaducada());
    }

    public function testSesionPreviaAlCambioSinMarcasNoCaduca(): void
    {
        // Sesiones ya abiertas cuando se desplego SEG-06 no tienen las marcas de tiempo;
        // no deben cerrarse de golpe (el primer request les pone admin_ultima_actividad).
        $this->assertFalse(Auth::sesionCaducada());
    }

    public function testRegistrarActividadRenuevaLaMarca(): void
    {
        $_SESSION['admin_ultima_actividad'] = time() - 5000;
        Auth::registrarActividad();

        $this->assertGreaterThan(time() - 2, $_SESSION['admin_ultima_actividad']);
    }
}
