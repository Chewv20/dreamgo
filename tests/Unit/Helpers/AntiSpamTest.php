<?php

namespace Tests\Unit\Helpers;

use App\Helpers\AntiSpam;
use PHPUnit\Framework\TestCase;

final class AntiSpamTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testCamposIncluyeHoneypotOcultoYRegistraLaHora(): void
    {
        $html = AntiSpam::campos('cotizador');

        $this->assertStringContainsString('name="' . AntiSpam::CAMPO_HONEYPOT . '"', $html);
        $this->assertStringContainsString('class="sr-only"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertFalse(AntiSpam::demasiadoRapido('cotizador', time() + AntiSpam::MIN_SEGUNDOS));
    }

    public function testHoneypotLleno(): void
    {
        $this->assertFalse(AntiSpam::honeypotLleno(null));
        $this->assertFalse(AntiSpam::honeypotLleno('   '));
        $this->assertTrue(AntiSpam::honeypotLleno('https://spam.example'));
    }

    public function testEnvioSinFormularioPintadoODemasiadoRapidoSeRechaza(): void
    {
        $this->assertTrue(AntiSpam::demasiadoRapido('contacto'));

        AntiSpam::campos('contacto');
        $this->assertTrue(AntiSpam::demasiadoRapido('contacto', time() + 1));
        // Cada formulario lleva su propio reloj.
        $this->assertTrue(AntiSpam::demasiadoRapido('cotizador', time() + 60));
    }
}
