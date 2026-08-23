<?php

namespace App\Services;

final class WhatsAppLinkService
{
    /**
     * @param array<string, string> $datos
     */
    public function generarLink(string $numeroDestino, string $mensaje): string
    {
        $numero = preg_replace('/\D+/', '', $numeroDestino);

        return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensaje);
    }

    public function generarLinkCotizacionPaquete(string $numeroDestino, string $tituloPaquete): string
    {
        $mensaje = "Hola, me gustaria cotizar el paquete \"{$tituloPaquete}\". ¿Me pueden ayudar con la disponibilidad?";

        return $this->generarLink($numeroDestino, $mensaje);
    }

    public function generarLinkCotizacionGeneral(string $numeroDestino, array $datos): string
    {
        $mensaje = "Hola, quiero cotizar un viaje con Dream Go.\n"
            . "Nombre: {$datos['nombre']}\n"
            . 'Personas: ' . ($datos['num_personas'] ?? 'Por definir') . "\n"
            . 'Fecha tentativa: ' . ($datos['fecha_tentativa'] ?? 'Por definir');

        return $this->generarLink($numeroDestino, $mensaje);
    }
}
