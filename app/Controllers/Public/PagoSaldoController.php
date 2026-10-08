<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\RateLimiter;
use App\Models\ConfiguracionSitio;
use App\Models\Reserva;
use App\Services\MercadoPagoService;
use App\Services\ReservaService;
use Core\Controller;
use RuntimeException;

/**
 * Cobro del saldo pendiente de una reserva confirmada, sin cuenta.
 *   GET  /reserva/{codigo}/pagar-saldo?t={token}  -> pagina con el saldo y el boton de pago
 *   POST /reserva/{codigo}/pagar-saldo            -> crea la preferencia de Mercado Pago y
 *                                                    redirige a Checkout Pro
 * El cliente puede liquidar todo o abonar una parte (minimo configurable en
 * `abono_minimo_saldo`); cada abono entra como un pago "saldo" mas en pagos_reserva, que ya
 * es aditivo. Gateado por codigo + token_publico (mismo criterio que el comprobante). El
 * saldo lo calcula el servidor y ReservaService::resolverMontoSaldo() acota lo que el cliente
 * escriba a [minimo, saldo].
 */
class PagoSaldoController extends Controller
{
    public function mostrar(string $codigo): void
    {
        $this->render($this->cargar($codigo));
    }

    public function iniciar(string $codigo): void
    {
        $this->verifyCsrf();

        $ip = $this->request->ip();
        if (RateLimiter::demasiados('pagar_saldo', null, $ip)) {
            RateLimiter::registrar('pagar_saldo', null, $ip);
            $this->abort(429, 'Demasiados intentos. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('pagar_saldo', null, $ip);

        $reserva = $this->cargar($codigo);
        $saldo = $this->saldo($reserva);

        if ($reserva['estado'] !== 'confirmada' || $saldo <= 0) {
            $this->render($reserva);

            return;
        }

        try {
            $monto = ReservaService::resolverMontoSaldo(
                $saldo,
                $this->abonoMinimo(),
                (string) $this->request->input('modo', 'total'),
                (string) $this->request->input('monto', '')
            );
        } catch (RuntimeException $e) {
            $this->render($reserva, $e->getMessage(), (string) $this->request->input('monto', ''));

            return;
        }

        $accessToken = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        if ($accessToken === '') {
            $this->render($reserva, 'El pago en línea no está disponible en este momento. Escríbenos y coordinamos el pago del saldo por otro medio.');

            return;
        }

        try {
            $preferencia = (new MercadoPagoService($accessToken))->crearPreferencia(
                $reserva,
                $monto,
                $reserva['paquete_moneda'] ?? 'MXN',
                (string) ($_ENV['APP_URL'] ?? ''),
                'saldo',
                $monto < $saldo
            );
        } catch (RuntimeException $e) {
            error_log('[PagoSaldoController] No se pudo crear la preferencia de saldo: ' . $e->getMessage());
            $this->render($reserva, 'No pudimos iniciar el pago en línea en este momento. Intenta más tarde o contáctanos.');

            return;
        }

        $this->redirect($preferencia['init_point']);
    }

    private function cargar(string $codigo): array
    {
        $token = (string) $this->request->input('t', '');
        $reserva = Reserva::porCodigoYToken(strtoupper(trim($codigo)), $token);

        if (!$reserva) {
            $this->abort(404);
        }

        return $reserva;
    }

    private function saldo(array $reserva): float
    {
        return round(max(0, (float) $reserva['precio_total'] - (float) $reserva['monto_pagado']), 2);
    }

    private function abonoMinimo(): float
    {
        return max(0.0, (float) ConfiguracionSitio::get('abono_minimo_saldo', '500'));
    }

    private function render(array $reserva, ?string $error = null, string $montoAbono = ''): void
    {
        $saldo = $this->saldo($reserva);
        $minimo = $this->abonoMinimo();

        $this->view('public/pago-saldo/formulario', [
            'reserva' => $reserva,
            'saldo' => $saldo,
            'pagable' => $reserva['estado'] === 'confirmada' && $saldo > 0,
            'abonoMinimo' => $minimo,
            'permiteAbono' => $saldo > $minimo,
            'montoAbono' => $montoAbono,
            'error' => $error,
        ], ['title' => 'Pagar saldo ' . $reserva['codigo_reserva'] . ' | Dream Go Operadora Turística']);
    }
}
