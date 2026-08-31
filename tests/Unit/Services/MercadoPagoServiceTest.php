<?php

namespace Tests\Unit\Services;

use App\Services\MercadoPagoService;
use PHPUnit\Framework\TestCase;

/**
 * verificarFirmaWebhook() es la unica barrera contra notificaciones de pago falsificadas
 * cuando MP_WEBHOOK_SECRET esta configurado (ver hallazgo de auditoria 2026-08-25: sin
 * secreto configurado, el webhook no verifica nada). Se cubre aca por ser una funcion pura,
 * sin llamadas HTTP ni base de datos, y la pieza de mas riesgo del flujo de pago.
 */
final class MercadoPagoServiceTest extends TestCase
{
    private const SECRET = 'test-secret';

    private function firmar(string $dataId, string $requestId, string $ts): string
    {
        $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
        $hmac = hash_hmac('sha256', $manifest, self::SECRET);

        return "ts={$ts},v1={$hmac}";
    }

    public function testFirmaValidaEsAceptada(): void
    {
        $service = new MercadoPagoService('token-no-usado');
        $xSignature = $this->firmar('123456789', 'req-1', '1700000000');

        $this->assertTrue(
            $service->verificarFirmaWebhook($xSignature, 'req-1', '123456789', self::SECRET)
        );
    }

    public function testDataIdDistintoAlFirmadoEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');
        $xSignature = $this->firmar('123456789', 'req-1', '1700000000');

        $this->assertFalse(
            $service->verificarFirmaWebhook($xSignature, 'req-1', '999999999', self::SECRET)
        );
    }

    public function testRequestIdDistintoAlFirmadoEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');
        $xSignature = $this->firmar('123456789', 'req-1', '1700000000');

        $this->assertFalse(
            $service->verificarFirmaWebhook($xSignature, 'req-otro', '123456789', self::SECRET)
        );
    }

    public function testSecretoDistintoEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');
        $xSignature = $this->firmar('123456789', 'req-1', '1700000000');

        $this->assertFalse(
            $service->verificarFirmaWebhook($xSignature, 'req-1', '123456789', 'otro-secreto')
        );
    }

    public function testDataIdEsInsensibleAMayusculas(): void
    {
        // El manifest usa strtolower($dataId); un data.id con mayusculas (poco comun pero
        // posible segun como llegue el query string) debe seguir validando igual.
        $service = new MercadoPagoService('token-no-usado');
        $xSignature = $this->firmar('abc123', 'req-1', '1700000000');

        $this->assertTrue(
            $service->verificarFirmaWebhook($xSignature, 'req-1', 'ABC123', self::SECRET)
        );
    }

    public function testHeaderSinTsEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse(
            $service->verificarFirmaWebhook('v1=cualquierhash', 'req-1', '123456789', self::SECRET)
        );
    }

    public function testHeaderSinV1EsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse(
            $service->verificarFirmaWebhook('ts=1700000000', 'req-1', '123456789', self::SECRET)
        );
    }

    public function testHeaderVacioEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse(
            $service->verificarFirmaWebhook('', 'req-1', '123456789', self::SECRET)
        );
    }

    public function testHeaderMalformadoNoLanzaExcepcion(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse(
            $service->verificarFirmaWebhook('esto-no-es-el-formato-esperado', 'req-1', '123456789', self::SECRET)
        );
    }

    public function testTsAlteradoDespuesDeFirmarEsRechazado(): void
    {
        // Cambiar el ts sin re-firmar (replay con timestamp distinto) debe fallar porque
        // el hash fue calculado sobre el ts original.
        $service = new MercadoPagoService('token-no-usado');
        $firmaOriginal = $this->firmar('123456789', 'req-1', '1700000000');
        [, $v1Parte] = explode(',', $firmaOriginal, 2);
        $xSignatureAlterado = 'ts=1800000000,' . $v1Parte;

        $this->assertFalse(
            $service->verificarFirmaWebhook($xSignatureAlterado, 'req-1', '123456789', self::SECRET)
        );
    }

    public function testTsRecienteEsAceptado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertTrue($service->tsWebhookEsReciente('ts=' . time() . ',v1=hash'));
    }

    public function testTsViejoEsRechazado(): void
    {
        // Una notificacion valida capturada hace horas: firma intacta pero ts fuera de ventana.
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse($service->tsWebhookEsReciente('ts=1700000000,v1=hash'));
    }

    public function testTsEnElFuturoFueraDeVentanaEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse($service->tsWebhookEsReciente('ts=' . (time() + 4000) . ',v1=hash'));
    }

    public function testTsAusenteEsRechazado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertFalse($service->tsWebhookEsReciente('v1=hash'));
    }

    public function testTsDentroDeUnaToleranciaPersonalizadaEsAceptado(): void
    {
        $service = new MercadoPagoService('token-no-usado');

        $this->assertTrue($service->tsWebhookEsReciente('ts=' . (time() - 120) . ',v1=hash', 300));
        $this->assertFalse($service->tsWebhookEsReciente('ts=' . (time() - 600) . ',v1=hash', 300));
    }
}
