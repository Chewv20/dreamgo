<?php

namespace App\Controllers\Public;

use App\Models\Reserva;
use App\Services\ComprobanteReservaService;
use Core\Controller;
use Core\Response;

/**
 * Descarga publica del comprobante PDF de una reserva confirmada.
 * GET /reserva/{codigo}/comprobante?t={token_publico}
 * No lleva CSRF (es GET idempotente) ni rate-limiting propio: el token de 32 hex ya hace
 * el link no adivinable, mismo criterio que /suscribir/baja/{token}.
 */
class ComprobanteReservaController extends Controller
{
    public function descargar(string $codigo): void
    {
        $token = (string) $this->request->query('t', '');
        $reserva = Reserva::porCodigoYToken(strtoupper(trim($codigo)), $token);

        if (!$reserva || $reserva['estado'] !== 'confirmada') {
            $this->abort(404);
        }

        $servicio = new ComprobanteReservaService();

        Response::archivo(
            $servicio->nombreArchivo($reserva),
            $servicio->generarPdf($reserva),
            'application/pdf'
        );
    }
}
