<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\RateLimiter;
use App\Models\Reserva;
use App\Services\ComprobanteReservaService;
use Core\Controller;
use Core\Response;

/**
 * Descarga publica del comprobante PDF de una reserva confirmada.
 * GET /reserva/{codigo}/comprobante?t={token_publico}
 * No lleva CSRF (es GET idempotente). El token de 32 hex hace el link no adivinable; el
 * rate-limiting por IP (Auditoria 2026-08-31, SEG-04) acota el gasto de CPU de dompdf si el
 * token llega a filtrarse.
 */
class ComprobanteReservaController extends Controller
{
    public function descargar(string $codigo): void
    {
        $ip = $this->request->ip();
        if (RateLimiter::demasiados('comprobante', null, $ip)) {
            $this->abort(429, 'Demasiadas descargas seguidas. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('comprobante', null, $ip);

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
