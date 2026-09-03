<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class MailerService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function enviarNotificacionCotizacion(array $cotizacion): bool
    {
        $destino = \App\Models\ConfiguracionSitio::get('email_equipo_reportes');
        if (empty($destino)) {
            return false;
        }

        $asunto = 'Nueva cotización de ' . $cotizacion['nombre'];
        $html = $this->renderPlantilla('notificacion_cotizacion', ['cotizacion' => $cotizacion]);

        return $this->enviar($destino, $asunto, $html, 'cotizacion_equipo', 'cotizacion', (int) ($cotizacion['id'] ?? 0));
    }

    public function enviarReservaPendiente(array $reserva): bool
    {
        $asunto = 'Tu reserva ' . $reserva['codigo_reserva'] . ' está en revisión';
        $html = $this->renderPlantilla('reserva_pendiente', ['reserva' => $reserva]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'reserva_pendiente', 'reserva', (int) $reserva['id']);
    }

    public function enviarConfirmacionReserva(array $reserva): bool
    {
        $asunto = 'Reserva confirmada: ' . $reserva['codigo_reserva'];

        $html = $this->renderPlantilla('confirmacion_reserva', [
            'reserva' => $reserva,
            'urlComprobante' => $this->linkReserva($reserva, '/comprobante'),
        ]);

        return $this->enviar(
            $reserva['cliente_email'],
            $asunto,
            $html,
            'confirmacion_reserva',
            'reserva',
            (int) $reserva['id'],
            $this->comprobanteAdjunto($reserva)
        );
    }

    public function enviarRecordatorioSaldo(array $reserva): bool
    {
        $asunto = 'Saldo pendiente de tu reserva ' . $reserva['codigo_reserva'];
        $html = $this->renderPlantilla('recordatorio_saldo', [
            'reserva' => $reserva,
            'urlPagarSaldo' => $this->linkReserva($reserva, '/pagar-saldo'),
        ]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'recordatorio_saldo', 'reserva', (int) $reserva['id']);
    }

    public function enviarPagoRecibido(array $reserva): bool
    {
        $asunto = 'Pago recibido - reserva ' . $reserva['codigo_reserva'];
        $html = $this->renderPlantilla('pago_recibido', [
            'reserva' => $reserva,
            'urlComprobante' => $this->linkReserva($reserva, '/comprobante'),
        ]);

        return $this->enviar(
            $reserva['cliente_email'],
            $asunto,
            $html,
            'pago_recibido',
            'reserva',
            (int) $reserva['id'],
            $this->comprobanteAdjunto($reserva)
        );
    }

    /**
     * Link publico a una accion de la reserva (/comprobante, /pagar-saldo) con el token que
     * la hace no adivinable. Devuelve '' si la reserva no tiene token (no deberia pasar).
     */
    private function linkReserva(array $reserva, string $accion): string
    {
        if (empty($reserva['token_publico'])) {
            return '';
        }

        return rtrim($_ENV['APP_URL'] ?? '', '/')
            . '/reserva/' . rawurlencode((string) $reserva['codigo_reserva'])
            . $accion . '?t=' . rawurlencode((string) $reserva['token_publico']);
    }

    /**
     * @return array{contenido:string, nombre:string}|null  null si dompdf falla (el correo se
     *   manda igual, sin adjunto).
     */
    private function comprobanteAdjunto(array $reserva): ?array
    {
        try {
            $servicio = new ComprobanteReservaService();

            return ['contenido' => $servicio->generarPdf($reserva), 'nombre' => $servicio->nombreArchivo($reserva)];
        } catch (\Throwable $e) {
            error_log('[MailerService] No se pudo generar el comprobante PDF de la reserva ' . ($reserva['id'] ?? '?') . ': ' . $e->getMessage());

            return null;
        }
    }

    public function enviarRecordatorioViaje(array $reserva): bool
    {
        $asunto = 'Tu viaje se acerca: ' . $reserva['paquete_titulo'];
        $html = $this->renderPlantilla('recordatorio_viaje', ['reserva' => $reserva]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'recordatorio_viaje', 'reserva', (int) $reserva['id']);
    }

    public function enviarSolicitudResena(array $reserva): bool
    {
        $asunto = 'Cuéntanos qué tal estuvo tu viaje: ' . $reserva['paquete_titulo'];
        $urlResena = rtrim($_ENV['APP_URL'] ?? '', '/') . '/resena/' . $reserva['codigo_reserva'];
        $html = $this->renderPlantilla('solicitud_resena', ['reserva' => $reserva, 'urlResena' => $urlResena]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'solicitud_resena', 'reserva', (int) $reserva['id']);
    }

    public function enviarConfirmacionSuscripcion(array $suscriptor): bool
    {
        $asunto = 'Confirma tu suscripción a Dream Go';
        $urlConfirmar = rtrim($_ENV['APP_URL'] ?? '', '/') . '/suscribir/confirmar/' . $suscriptor['token'];
        $html = $this->renderPlantilla('confirmacion_suscripcion', ['urlConfirmar' => $urlConfirmar]);

        return $this->enviar($suscriptor['email'], $asunto, $html, 'confirmacion_suscripcion', 'suscriptor', (int) $suscriptor['id']);
    }

    public function enviarAvisoOferta(array $suscriptor, array $oferta): bool
    {
        $asunto = 'Nueva oferta: ' . $oferta['codigo'];
        $urlBaja = rtrim($_ENV['APP_URL'] ?? '', '/') . '/suscribir/baja/' . $suscriptor['token'];
        $html = $this->renderPlantilla('aviso_oferta', ['oferta' => $oferta, 'urlBaja' => $urlBaja]);

        return $this->enviar($suscriptor['email'], $asunto, $html, 'aviso_oferta', 'suscriptor', (int) $suscriptor['id']);
    }

    public function enviarReportePeriodico(string $destino, string $periodo, array $datos): bool
    {
        $asunto = 'Reporte ' . $periodo . ' de Dream Go';
        $html = $this->renderPlantilla('reporte_periodico', ['periodo' => $periodo] + $datos);

        return $this->enviar($destino, $asunto, $html, 'reporte_periodico');
    }

    private function renderPlantilla(string $nombre, array $datos): string
    {
        extract($datos, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/emails/' . $nombre . '.php';
        $cuerpo = ob_get_clean();

        ob_start();
        require BASE_PATH . '/app/Views/emails/layout_email.php';

        return ob_get_clean();
    }

    /**
     * @param array{contenido:string, nombre:string}|null $adjunto
     */
    private function enviar(
        string $destinatario,
        string $asunto,
        string $htmlBody,
        string $tipo,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?array $adjunto = null
    ): bool {
        $exitoso = false;
        $errorDetalle = null;

        try {
            $mail = $this->configurarPhpMailer();
            $mail->addAddress($destinatario);
            $mail->Subject = $asunto;
            $mail->Body = $htmlBody;
            if ($adjunto !== null) {
                $mail->addStringAttachment($adjunto['contenido'], $adjunto['nombre'], PHPMailer::ENCODING_BASE64, 'application/pdf');
            }
            $mail->send();
            $exitoso = true;
        } catch (PHPMailerException $e) {
            $errorDetalle = $e->getMessage();
            error_log('[MailerService] ' . $errorDetalle);
        }

        $this->registrarLog($tipo, $destinatario, $asunto, $referenciaTipo, $referenciaId, $exitoso, $errorDetalle);

        return $exitoso;
    }

    private function configurarPhpMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? '';
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        // Auditoria 2026-09, hallazgo PERF-01: sin esto PHPMailer usa Timeout = 300 s. Como el
        // envio es sincrono dentro del request (reserva publica, webhook de Mercado Pago), un
        // SMTP lento o caido bloqueaba la respuesta hasta 5 min. 10 s cubre de sobra un envio
        // sano; si falla, enviar() lo registra en log_correos_enviados como no exitoso.
        $mail->Timeout = 10;
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'no-reply@dreamgooperadoraturistica.com',
            $_ENV['SMTP_FROM_NAME'] ?? 'Dream Go Operadora Turística'
        );

        return $mail;
    }

    private function registrarLog(
        string $tipo,
        string $destinatario,
        string $asunto,
        ?string $referenciaTipo,
        ?int $referenciaId,
        bool $exitoso,
        ?string $errorDetalle
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO log_correos_enviados (tipo, destinatario, asunto, referencia_tipo, referencia_id, exitoso, error_detalle)
             VALUES (:tipo, :destinatario, :asunto, :referencia_tipo, :referencia_id, :exitoso, :error_detalle)'
        );
        $stmt->execute([
            'tipo' => $tipo,
            'destinatario' => $destinatario,
            'asunto' => $asunto,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
            'exitoso' => $exitoso ? 1 : 0,
            'error_detalle' => $errorDetalle,
        ]);
    }
}
