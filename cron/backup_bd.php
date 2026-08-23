<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use Core\Database;

const DIAS_RETENCION_BACKUPS = 14;

$config = require BASE_PATH . '/config/database.php';
$backupsDir = BASE_PATH . '/storage/backups';
$nombreArchivo = 'backup_' . date('Y-m-d_His') . '.sql';
$rutaSql = $backupsDir . '/' . $nombreArchivo;
$rutaComprimida = $rutaSql . '.gz';

$exitoMysqldump = intentarMysqldump($config, $rutaSql);

if (!$exitoMysqldump) {
    cron_log('backup_bd', 'mysqldump no disponible o fallo, usando respaldo 100% PHP.');
    backupPhpPuro(Database::connection(), $rutaSql);
}

comprimirYBorrarOriginal($rutaSql, $rutaComprimida);
rotarBackupsAntiguos($backupsDir);

cron_log('backup_bd', 'Backup generado: ' . basename($rutaComprimida) . ' (' . round(filesize($rutaComprimida) / 1024, 1) . ' KB).');

function intentarMysqldump(array $config, string $rutaSalida): bool
{
    if (!function_exists('shell_exec')) {
        return false;
    }

    $binario = trim((string) shell_exec('where mysqldump 2>NUL') ?: (string) shell_exec('which mysqldump 2>/dev/null'));

    $candidatos = array_filter([
        $binario !== '' ? explode("\n", $binario)[0] : null,
        'C:/Program Files/MariaDB 10.6/bin/mysqldump.exe',
        'mysqldump',
    ]);

    foreach ($candidatos as $mysqldump) {
        $mysqldump = trim($mysqldump);

        $cnfFile = tempnam(sys_get_temp_dir(), 'dgcnf');
        file_put_contents($cnfFile, sprintf(
            "[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n",
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port']
        ));
        chmod($cnfFile, 0600);

        $comando = sprintf(
            '"%s" --defaults-extra-file=%s --single-transaction --routines --triggers %s > %s 2>%s',
            $mysqldump,
            escapeshellarg($cnfFile),
            escapeshellarg($config['database']),
            escapeshellarg($rutaSalida),
            escapeshellarg($rutaSalida . '.err')
        );

        exec($comando, $salida, $codigoRetorno);
        @unlink($cnfFile);

        $errorLog = @file_get_contents($rutaSalida . '.err') ?: '';
        @unlink($rutaSalida . '.err');

        if ($codigoRetorno === 0 && is_file($rutaSalida) && filesize($rutaSalida) > 0) {
            return true;
        }

        cron_log('backup_bd', "Intento con '{$mysqldump}' fallo (codigo {$codigoRetorno}): " . trim($errorLog));
    }

    return false;
}

function backupPhpPuro(\PDO $db, string $rutaSalida): void
{
    $handle = fopen($rutaSalida, 'w');
    fwrite($handle, "-- Backup generado por respaldo PHP puro (fallback sin mysqldump)\n-- Fecha: " . date('c') . "\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    $tablas = $db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($tablas as $tabla) {
        $crear = $db->query('SHOW CREATE TABLE `' . $tabla . '`')->fetch();
        fwrite($handle, "DROP TABLE IF EXISTS `{$tabla}`;\n" . $crear['Create Table'] . ";\n\n");

        $filas = $db->query('SELECT * FROM `' . $tabla . '`');
        foreach ($filas as $fila) {
            $columnas = array_map(fn ($c) => '`' . $c . '`', array_keys($fila));
            $valores = array_map(function ($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote((string) $v);
            }, array_values($fila));

            fwrite($handle, "INSERT INTO `{$tabla}` (" . implode(',', $columnas) . ') VALUES (' . implode(',', $valores) . ");\n");
        }
        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);
}

function comprimirYBorrarOriginal(string $rutaSql, string $rutaComprimida): void
{
    $contenido = file_get_contents($rutaSql);
    file_put_contents($rutaComprimida, gzencode($contenido, 9));
    unlink($rutaSql);
}

function rotarBackupsAntiguos(string $backupsDir): void
{
    $limite = time() - (DIAS_RETENCION_BACKUPS * 86400);

    foreach (glob($backupsDir . '/backup_*.sql.gz') as $archivo) {
        if (filemtime($archivo) < $limite) {
            unlink($archivo);
        }
    }
}
