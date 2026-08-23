<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Services\ReservaService;
use Core\Database;

$service = new ReservaService(Database::connection());
$expiradas = $service->expirarVencidas();

cron_log('liberar_reservas_expiradas', count($expiradas) . ' reserva(s) expirada(s) y cupo liberado.');
