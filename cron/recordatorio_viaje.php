<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Models\ConfiguracionSitio;
use App\Services\MailerService;
use Core\Database;

$db = Database::connection();
$dias = (int) ConfiguracionSitio::get('dias_recordatorio_viaje', 3);

$stmt = $db->prepare(
    'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, s.fecha_salida, p.titulo AS paquete_titulo
     FROM reservas r
     INNER JOIN clientes c ON c.id = r.cliente_id
     INNER JOIN salidas s ON s.id = r.salida_id
     INNER JOIN paquetes p ON p.id = s.paquete_id
     WHERE r.estado = "confirmada"
       AND s.fecha_salida = DATE_ADD(CURDATE(), INTERVAL :dias DAY)
       AND NOT EXISTS (
         SELECT 1 FROM log_correos_enviados
         WHERE tipo = "recordatorio_viaje" AND referencia_tipo = "reserva" AND referencia_id = r.id AND exitoso = 1
       )'
);
$stmt->execute(['dias' => $dias]);
$reservas = $stmt->fetchAll();

$mailer = new MailerService($db);
$enviados = 0;

foreach ($reservas as $reserva) {
    if ($mailer->enviarRecordatorioViaje($reserva)) {
        $enviados++;
    }
}

cron_log('recordatorio_viaje', count($reservas) . ' reserva(s) encontrada(s), ' . $enviados . ' recordatorio(s) enviado(s) (dias_recordatorio_viaje=' . $dias . ').');
