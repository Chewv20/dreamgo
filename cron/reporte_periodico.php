<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Models\ConfiguracionSitio;
use App\Services\MailerService;
use Core\Database;

$periodo = 'diario';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--periodo=')) {
        $periodo = substr($arg, strlen('--periodo='));
    }
}

$horasAtras = $periodo === 'semanal' ? 24 * 7 : 24;
$desde = date('Y-m-d H:i:s', strtotime("-{$horasAtras} hours"));

$db = Database::connection();

$stmtCot = $db->prepare('SELECT COUNT(*) FROM cotizaciones WHERE creado_en >= :desde');
$stmtCot->execute(['desde' => $desde]);
$nuevasCotizaciones = (int) $stmtCot->fetchColumn();

$stmtRes = $db->prepare('SELECT COUNT(*) FROM reservas WHERE creado_en >= :desde');
$stmtRes->execute(['desde' => $desde]);
$nuevasReservas = (int) $stmtRes->fetchColumn();

$stmtConf = $db->prepare('SELECT COUNT(*) FROM reservas WHERE confirmada_en >= :desde');
$stmtConf->execute(['desde' => $desde]);
$reservasConfirmadas = (int) $stmtConf->fetchColumn();

$destino = ConfiguracionSitio::get('email_equipo_reportes');

if (empty($destino)) {
    cron_log('reporte_periodico', 'No se envio: no hay email_equipo_reportes configurado.');
    exit(0);
}

$mailer = new MailerService($db);
$enviado = $mailer->enviarReportePeriodico($destino, $periodo, [
    'nuevasCotizaciones' => $nuevasCotizaciones,
    'nuevasReservas' => $nuevasReservas,
    'reservasConfirmadas' => $reservasConfirmadas,
]);

cron_log('reporte_periodico', "Periodo={$periodo} cotizaciones={$nuevasCotizaciones} reservas={$nuevasReservas} confirmadas={$reservasConfirmadas} enviado=" . ($enviado ? 'si' : 'no'));
