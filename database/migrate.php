<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';

use Core\Database;

function migrate_log(string $mensaje): void
{
    echo sprintf('[%s] %s' . PHP_EOL, date('Y-m-d H:i:s'), $mensaje);
}

$db = Database::connection();

$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migracion VARCHAR(191) NOT NULL UNIQUE,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$aplicadas = $db->query('SELECT migracion FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$aplicadas = array_flip($aplicadas);

$archivos = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($archivos, SORT_STRING);

$pendientes = array_values(array_filter(
    $archivos,
    fn (string $ruta): bool => !isset($aplicadas[basename($ruta)])
));

if ($pendientes === []) {
    migrate_log('Nada que aplicar, la base de datos ya esta al dia (' . count($aplicadas) . ' migracion(es) aplicada(s)).');
    exit(0);
}

$marcarAplicada = $db->prepare('INSERT INTO schema_migrations (migracion) VALUES (:migracion)');

foreach ($pendientes as $ruta) {
    $nombre = basename($ruta);
    $sql = file_get_contents($ruta);

    // Sin transaccion: MySQL/MariaDB hace commit implicito en cada sentencia DDL (CREATE,
    // ALTER, DROP INDEX...), asi que PDO::beginTransaction()/commit() no protegen nada aqui
    // y con una sola sentencia DDL en el archivo revientan con "There is no active
    // transaction" porque el commit implicito ya cerro la transaccion antes de llegar al
    // commit() explicito. Si una sentencia falla a mitad de un archivo con varias, las
    // anteriores quedan aplicadas igual: por eso cada migracion debe ser chica y
    // autocontenida (ver database/migrations/README.md).
    // Quita las lineas que son 100% comentario ANTES de partir por ";". Filtrar por
    // statement completo (como se hacia antes) fallaba cuando un archivo empezaba con
    // comentarios seguidos de la primera sentencia real en el mismo trozo (antes del
    // primer ";"): el trozo completo arrancaba con "--" y se descartaba entero, sentencia
    // real incluida, sin ningun error visible.
    $sqlSinComentarios = preg_replace('/^\s*--.*$/m', '', $sql);
    $sentencias = array_filter(
        array_map('trim', explode(';', $sqlSinComentarios)),
        fn (string $s): bool => $s !== ''
    );

    try {
        foreach ($sentencias as $sentencia) {
            $db->exec($sentencia);
        }

        $marcarAplicada->execute(['migracion' => $nombre]);

        migrate_log("Aplicada: {$nombre}");
    } catch (PDOException $e) {
        migrate_log("ERROR aplicando {$nombre}: " . $e->getMessage());
        migrate_log("Revisa el estado de la base de datos a mano: la migracion pudo quedar aplicada parcialmente.");
        exit(1);
    }
}

migrate_log('Listo. ' . count($pendientes) . ' migracion(es) aplicada(s).');
