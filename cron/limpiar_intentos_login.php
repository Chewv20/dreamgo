<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Core\Database;

$stmt = Database::connection()->prepare(
    'DELETE FROM intentos_login WHERE creado_en < (NOW() - INTERVAL 30 DAY)'
);
$stmt->execute();

cron_log('limpiar_intentos_login', $stmt->rowCount() . ' registro(s) de intentos de login purgado(s).');
