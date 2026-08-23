<?php

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

        $asunto = 'Nueva cotizacion de ' . $cotizacion['nombre'];
        $html = $this->renderPlantilla('notificacion_cotizacion', ['cotizacion' => $cotizacion]);

        return $this->enviar($destino, $asunto, $html, 'cotizacion_equipo', 'cotizacion', (int) ($cotizacion['id'] ?? 0));
    }

    public function enviarReservaPendiente(array $reserva): bool
    {
        $asunto = 'Tu reserva ' . $reserva['codigo_reserva'] . ' esta en revision';
        $html = $this->renderPlantilla('reserva_pendiente', ['reserva' => $reserva]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'confirmacion_reserva', 'reserva', (int) $reserva['id']);
    }

    public function enviarConfirmacionReserva(array $reserva): bool
    {
        $asunto = 'Reserva confirmada: ' . $reserva['codigo_reserva'];
        $html = $this->renderPlantilla('confirmacion_reserva', ['reserva' => $reserva]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'confirmacion_reserva', 'reserva', (int) $reserva['id']);
    }

    public function enviarRecordatorioViaje(array $reserva): bool
    {
        $asunto = 'Tu viaje se acerca: ' . $reserva['paquete_titulo'];
        $html = $this->renderPlantilla('recordatorio_viaje', ['reserva' => $reserva]);

        return $this->enviar($reserva['cliente_email'], $asunto, $html, 'recordatorio_viaje', 'reserva', (int) $reserva['id']);
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

    private function enviar(
        string $destinatario,
        string $asunto,
        string $htmlBody,
        string $tipo,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null
    ): bool {
        $exitoso = false;
        $errorDetalle = null;

        try {
            $mail = $this->configurarPhpMailer();
            $mail->addAddress($destinatario);
            $mail->Subject = $asunto;
            $mail->Body = $htmlBody;
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
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'no-reply@dreamgooperadoraturistica.com',
            $_ENV['SMTP_FROM_NAME'] ?? 'Dream Go Operadora Turistica'
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
