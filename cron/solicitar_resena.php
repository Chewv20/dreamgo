<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Models\ConfiguracionSitio;
use App\Services\MailerService;
use Core\Database;

$db = Database::connection();
$dias = (int) ConfiguracionSitio::get('dias_solicitud_resena', 3);

$stmt = $db->prepare(
    'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, s.fecha_salida, s.fecha_regreso, p.titulo AS paquete_titulo
     FROM reservas r
     INNER JOIN clientes c ON c.id = r.cliente_id
     INNER JOIN salidas s ON s.id = r.salida_id
     INNER JOIN paquetes p ON p.id = s.paquete_id
     WHERE r.estado = "confirmada"
       AND COALESCE(s.fecha_regreso, s.fecha_salida) = DATE_SUB(CURDATE(), INTERVAL :dias DAY)
       AND NOT EXISTS (
         SELECT 1 FROM log_correos_enviados
         WHERE tipo = "solicitud_resena" AND referencia_tipo = "reserva" AND referencia_id = r.id AND exitoso = 1
       )
       AND NOT EXISTS (SELECT 1 FROM resenas WHERE reserva_id = r.id)'
);
$stmt->execute(['dias' => $dias]);
$reservas = $stmt->fetchAll();

$mailer = new MailerService($db);
$enviados = 0;

foreach ($reservas as $reserva) {
    if ($mailer->enviarSolicitudResena($reserva)) {
        $enviados++;
    }
}

cron_log('solicitar_resena', count($reservas) . ' reserva(s) encontrada(s), ' . $enviados . ' solicitud(es) de resena enviada(s) (dias_solicitud_resena=' . $dias . ').');
