<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Core\Database;

$stmt = Database::connection()->prepare(
    'UPDATE codigos_descuento SET activo = 0 WHERE activo = 1 AND fecha_fin < CURDATE()'
);
$stmt->execute();

cron_log('desactivar_ofertas_vencidas', $stmt->rowCount() . ' codigo(s) de descuento desactivado(s) por vencimiento.');
