<?php

namespace App\Services;

use RuntimeException;

/**
 * Integracion con Mercado Pago (Checkout Pro) via API REST directa con curl.
 * Sin SDK de Composer a proposito: el proyecto no usa clientes HTTP pesados
 * (solo phpmailer y dotenv), y la integracion es apenas 2 endpoints.
 */
final class MercadoPagoService
{
    private const API_BASE = 'https://api.mercadopago.com';

    public function __construct(private readonly string $accessToken)
    {
    }

    /**
     * @param array{id:int, codigo_reserva:string, paquete_titulo:string} $reserva
     * @return array<string, mixed> respuesta decodificada de Mercado Pago (incluye init_point)
     */
    public function crearPreferencia(array $reserva, float $montoAnticipo, string $moneda, string $appUrl): array
    {
        $backUrl = rtrim($appUrl, '/') . '/reservar/' . $reserva['codigo_reserva'] . '/gracias';

        $body = [
            'items' => [[
                'title' => 'Anticipo reserva ' . $reserva['codigo_reserva'] . ' - ' . $reserva['paquete_titulo'],
                'quantity' => 1,
                'currency_id' => $moneda,
                'unit_price' => $montoAnticipo,
            ]],
            'external_reference' => (string) $reserva['id'],
            'back_urls' => [
                'success' => $backUrl . '?status=approved',
                'pending' => $backUrl . '?status=pending',
                'failure' => $backUrl . '?status=failure',
            ],
            'auto_return' => 'approved',
            'notification_url' => rtrim($appUrl, '/') . '/webhooks/mercadopago',
        ];

        $respuesta = $this->httpJson('POST', self::API_BASE . '/checkout/preferences', $body);

        if ($respuesta['status'] < 200 || $respuesta['status'] >= 300 || empty($respuesta['body']['init_point'])) {
            throw new RuntimeException(
                'Mercado Pago no devolvio un init_point valido (status ' . $respuesta['status'] . '): '
                . json_encode($respuesta['body'])
            );
        }

        return $respuesta['body'];
    }

    /**
     * @return array<string, mixed> el pago tal cual lo reporta Mercado Pago (status, external_reference, transaction_amount...)
     */
    public function obtenerPago(string $paymentId): array
    {
        $respuesta = $this->httpJson('GET', self::API_BASE . '/v1/payments/' . rawurlencode($paymentId), null);

        if ($respuesta['status'] < 200 || $respuesta['status'] >= 300) {
            throw new RuntimeException(
                'No se pudo obtener el pago ' . $paymentId . ' de Mercado Pago (status ' . $respuesta['status'] . ')'
            );
        }

        return $respuesta['body'];
    }

    /**
     * Formato documentado por Mercado Pago para el header x-signature:
     * "ts=1704908010,v1=<hmac_sha256_hex>". El manifest a firmar es
     * "id:{dataId};request-id:{xRequestId};ts:{ts};".
     */
    public function verificarFirmaWebhook(string $xSignature, string $xRequestId, string $dataId, string $secret): bool
    {
        $partes = [];
        foreach (explode(',', $xSignature) as $par) {
            [$clave, $valor] = array_pad(explode('=', trim($par), 2), 2, '');
            $partes[$clave] = $valor;
        }

        if (empty($partes['ts']) || empty($partes['v1'])) {
            return false;
        }

        $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $xRequestId . ';ts:' . $partes['ts'] . ';';
        $hashEsperado = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hashEsperado, $partes['v1']);
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private function httpJson(string $method, string $url, ?array $body): array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POSTFIELDS => $body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE) : null,
        ]);

        $respuesta = curl_exec($ch);
        $error = curl_errno($ch);
        $errorMsg = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== 0) {
            throw new RuntimeException('Error de conexion con Mercado Pago: ' . $errorMsg);
        }

        $decodificado = json_decode((string) $respuesta, true);

        return [
            'status' => $status,
            'body' => is_array($decodificado) ? $decodificado : [],
        ];
    }
}
