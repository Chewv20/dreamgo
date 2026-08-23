<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';

function cron_log(string $script, string $mensaje): void
{
    $linea = sprintf('[%s] [%s] %s' . PHP_EOL, date('Y-m-d H:i:s'), $script, $mensaje);
    file_put_contents(BASE_PATH . '/storage/logs/cron.log', $linea, FILE_APPEND);
    echo $linea;
}
