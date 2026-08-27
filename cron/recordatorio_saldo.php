<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Models\ConfiguracionSitio;
use App\Services\MailerService;
use Core\Database;

$db = Database::connection();
$dias = (int) ConfiguracionSitio::get('dias_recordatorio_saldo', 7);

$stmt = $db->prepare(
    'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email,
            s.fecha_salida, p.titulo AS paquete_titulo, p.moneda AS paquete_moneda
     FROM reservas r
     INNER JOIN clientes c ON c.id = r.cliente_id
     INNER JOIN salidas s ON s.id = r.salida_id
     INNER JOIN paquetes p ON p.id = s.paquete_id
     WHERE r.estado = "confirmada"
       AND r.monto_pagado < r.precio_total
       AND s.fecha_salida = DATE_ADD(CURDATE(), INTERVAL :dias DAY)
       AND NOT EXISTS (
         SELECT 1 FROM log_correos_enviados
         WHERE tipo = "recordatorio_saldo" AND referencia_tipo = "reserva" AND referencia_id = r.id AND exitoso = 1
       )'
);
$stmt->execute(['dias' => $dias]);
$reservas = $stmt->fetchAll();

$mailer = new MailerService($db);
$enviados = 0;

foreach ($reservas as $reserva) {
    if ($mailer->enviarRecordatorioSaldo($reserva)) {
        $enviados++;
    }
}

cron_log('recordatorio_saldo', count($reservas) . ' reserva(s) con saldo encontrada(s), ' . $enviados . ' recordatorio(s) enviado(s) (dias_recordatorio_saldo=' . $dias . ').');
