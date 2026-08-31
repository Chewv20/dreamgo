<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Models\Reserva;
use App\Services\MailerService;
use App\Services\MercadoPagoService;
use App\Services\ReservaService;
use Core\Controller;
use Core\Response;
use RuntimeException;

/**
 * Recibe las notificaciones de Mercado Pago (Checkout Pro). Nunca confia en el cuerpo/query
 * de la notificacion para el estado o el monto del pago -- siempre vuelve a pedir el pago a
 * la API de Mercado Pago con nuestro propio access token antes de confirmar nada.
 */
class MercadoPagoWebhookController extends Controller
{
    public function notificar(): void
    {
        // PHP convierte automaticamente los puntos de los nombres de parametros de query
        // string en guiones bajos, asi que "data.id" (formato actual de Mercado Pago) llega
        // a $_GET como "data_id", no como "data.id". El formato viejo (?topic=payment&id=X)
        // no tiene puntos y no se ve afectado.
        $tipo = (string) $this->request->query('type', $this->request->query('topic', ''));
        $paymentId = (string) $this->request->query('data_id', $this->request->query('id', ''));

        if ($tipo !== 'payment' || $paymentId === '') {
            $this->json(['ok' => true, 'ignorado' => true]);
        }

        $secret = $_ENV['MP_WEBHOOK_SECRET'] ?? '';
        $accessToken = $_ENV['MP_ACCESS_TOKEN'] ?? '';

        if ($accessToken === '') {
            error_log('[MercadoPagoWebhook] Notificacion recibida pero MP_ACCESS_TOKEN no esta configurado.');
            $this->json(['ok' => false], 200);
        }

        $servicio = new MercadoPagoService($accessToken);

        if ($secret !== '') {
            $xSignature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
            $xRequestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

            if (!$servicio->verificarFirmaWebhook($xSignature, $xRequestId, $paymentId, $secret)) {
                error_log('[MercadoPagoWebhook] Firma invalida para el pago ' . $paymentId);
                $this->json(['error' => 'Firma invalida'], 401);
            }

            // Auditoria 2026-08-31, hallazgo SEG-03: firma valida pero con un ts viejo = replay
            // de una notificacion capturada. Se rechaza aunque el HMAC cuadre.
            if (!$servicio->tsWebhookEsReciente($xSignature)) {
                error_log('[MercadoPagoWebhook] Timestamp fuera de ventana (posible replay) para el pago ' . $paymentId);
                $this->json(['error' => 'Notificacion expirada'], 401);
            }
        } elseif (($_ENV['APP_ENV'] ?? 'production') !== 'local') {
            // Auditoria 2026-08-25, hallazgo SEG-03: sin secreto configurado, cualquiera podia
            // invocar este endpoint con un data.id arbitrario y forzar el momento de la
            // confirmacion (la re-consulta a la API real de MP evita falsificar el pago en si,
            // pero no evita el abuso del endpoint). Fuera de 'local' se rechaza de plano en vez
            // de seguir sin verificar; en 'local' se permite para poder probar el webhook sin
            // configurar MP_WEBHOOK_SECRET (ver .env.example).
            error_log('[MercadoPagoWebhook] MP_WEBHOOK_SECRET no configurado: notificacion rechazada.');
            $this->json(['error' => 'Webhook no configurado'], 401);
        } else {
            error_log('[MercadoPagoWebhook] MP_WEBHOOK_SECRET no configurado: la notificacion no se esta verificando (APP_ENV=local).');
        }

        try {
            $pago = $servicio->obtenerPago($paymentId);
        } catch (RuntimeException $e) {
            error_log('[MercadoPagoWebhook] No se pudo obtener el pago ' . $paymentId . ': ' . $e->getMessage());
            $this->json(['ok' => false], 200);
        }

        if (($pago['status'] ?? '') !== 'approved') {
            $this->json(['ok' => true, 'estado_pago' => $pago['status'] ?? null]);
        }

        [$reservaId, $concepto] = ReservaService::parseReferenciaExterna((string) ($pago['external_reference'] ?? ''));
        $reserva = $reservaId > 0 ? Reserva::find($reservaId) : false;

        if (!$reserva) {
            error_log('[MercadoPagoWebhook] Pago ' . $paymentId . ' aprobado pero external_reference no corresponde a ninguna reserva.');
            $this->json(['ok' => true]);
        }

        $service = new ReservaService($this->db);
        $resultado = $service->registrarPagoAprobado(
            $reservaId,
            (string) $paymentId,
            (float) ($pago['transaction_amount'] ?? 0),
            $concepto
        );

        // Auditoria 2026-09, hallazgo PERF-01: se responde 200 a Mercado Pago ANTES de enviar
        // el correo. enviarConfirmacionReserva()/enviarPagoRecibido() hacen un envio SMTP que
        // puede tardar segundos; si la respuesta se demora, Mercado Pago da la notificacion por
        // fallida y la reintenta (el dedup por UNIQUE(referencia_pago) evita el doble cobro,
        // pero genera trabajo repetido y ruido en el log).
        Response::jsonYContinuar(['ok' => true, 'resultado' => $resultado]);

        if ($resultado === ReservaService::PAGO_CONFIRMO) {
            (new MailerService($this->db))->enviarConfirmacionReserva(Reserva::conDetalle($reservaId));
        } elseif ($resultado === ReservaService::PAGO_REGISTRADO) {
            (new MailerService($this->db))->enviarPagoRecibido(Reserva::conDetalle($reservaId));
        }
    }
}
