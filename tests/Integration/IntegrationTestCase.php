<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\ConfiguracionSitio;
use Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base de las pruebas de integracion (auditoria 2026-09, hallazgo CAL-01 / PENDIENTES #1).
 *
 * Estrategia de aislamiento: NO se envuelve cada test en una transaccion con rollback,
 * porque el codigo que interesa probar (ReservaService, DescuentoService) abre sus propias
 * transacciones con `FOR UPDATE` y no se puede anidar. En su lugar:
 *   - setUpBeforeClass(): borra todas las tablas y reaplica `database/schema.sql` una vez.
 *   - setUp(): TRUNCATE de las tablas transaccionales (TABLAS_A_LIMPIAR, no todas: en
 *     Windows/InnoDB cada TRUNCATE recrea el tablespace y es caro) + siembra un fixture
 *     minimo y determinista (1 categoria, 1 paquete publicado a 1000 MXN, 1 salida abierta
 *     con cupo 10, y la config `porcentaje_anticipo_reserva = 50`). Una subclase que toque
 *     otras tablas puede sobreescribir tablasALimpiar().
 *
 * Si no hay conexion a la base de pruebas, la clase entera se marca como skipped con
 * instrucciones, para que `composer test:integration` no reviente en un entorno sin BD.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static PDO $db;

    private static ?string $motivoSkip = null;

    /** Fixture base determinista (AUTO_INCREMENT en 1 tras el TRUNCATE). */
    protected const SALIDA_ID = 1;
    protected const PAQUETE_ID = 1;
    protected const PRECIO_PAQUETE = 1000.0;
    protected const CUPO_INICIAL = 10;

    /**
     * Tablas que se vacian antes de cada test. Hijos primero (con FOREIGN_KEY_CHECKS = 0 el
     * orden no es estricto, pero se deja legible). Solo las que la suite escribe: TRUNCATE es
     * DDL y en Windows/InnoDB cuesta ~0.5 s por tabla.
     *
     * @return list<string>
     */
    protected static function tablasALimpiar(): array
    {
        return [
            'pagos_reserva',
            'resenas',
            'reservas',
            'salidas',
            'codigos_descuento',
            'paquetes',
            'categorias',
            'clientes',
            'configuracion_sitio',
            'log_correos_enviados',
            'intentos_accion',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        try {
            self::$db = Database::connection();
            self::$db->query('SELECT 1');
            self::recrearEsquema();
        } catch (\Throwable $e) {
            // No se aborta aqui: se guarda el motivo y cada test se marca skipped en setUp(),
            // asi el reporte muestra "S" con instrucciones en vez de "No tests executed".
            self::$motivoSkip =
                'Sin base de pruebas "' . ($_ENV['DB_NAME'] ?? '?') . '": ' . $e->getMessage()
                . ' | Crea la base y reintenta: '
                . 'mysql -u root -e "CREATE DATABASE dreamgo_test CHARACTER SET utf8mb4" && composer test:integration';
        }
    }

    protected function setUp(): void
    {
        if (self::$motivoSkip !== null) {
            self::markTestSkipped(self::$motivoSkip);
        }

        self::limpiar();
        self::sembrarFixtureBase();
    }

    // --- helpers de infraestructura -------------------------------------------------------

    private static function recrearEsquema(): void
    {
        self::$db->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::tablas() as $tabla) {
            self::$db->exec('DROP TABLE IF EXISTS `' . $tabla . '`');
        }
        self::$db->exec('SET FOREIGN_KEY_CHECKS = 1');

        self::ejecutarArchivoSql(dirname(__DIR__, 2) . '/database/schema.sql');
    }

    private static function limpiar(): void
    {
        self::$db->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (static::tablasALimpiar() as $tabla) {
            self::$db->exec('TRUNCATE TABLE `' . $tabla . '`');
        }
        self::$db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private static function sembrarFixtureBase(): void
    {
        self::$db->exec(
            "INSERT INTO categorias (id, nombre, slug, tipo) VALUES (1, 'Pruebas', 'pruebas', 'nacional')"
        );
        self::$db->exec(sprintf(
            "INSERT INTO paquetes (id, categoria_id, titulo, slug, precio_desde, moneda, estado)
             VALUES (%d, 1, 'Paquete de prueba', 'paquete-de-prueba', %0.2f, 'MXN', 'publicado')",
            self::PAQUETE_ID,
            self::PRECIO_PAQUETE
        ));
        self::$db->exec(sprintf(
            "INSERT INTO salidas (id, paquete_id, fecha_salida, cupo_maximo, cupo_disponible, estado)
             VALUES (%d, %d, DATE_ADD(CURDATE(), INTERVAL 30 DAY), %d, %d, 'abierta')",
            self::SALIDA_ID,
            self::PAQUETE_ID,
            self::CUPO_INICIAL,
            self::CUPO_INICIAL
        ));

        ConfiguracionSitio::set('horas_expiracion_reserva', '48');
        ConfiguracionSitio::set('porcentaje_anticipo_reserva', '50');
    }

    /** @return list<string> */
    private static function tablas(): array
    {
        return self::$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function ejecutarArchivoSql(string $ruta): void
    {
        $sql = (string) file_get_contents($ruta);
        // Mismo criterio que database/migrate.php: quita lineas 100% comentario antes de
        // partir por ";". schema.sql no tiene DELIMITER ni ";" dentro de cadenas.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
            self::$db->exec($sentencia);
        }
    }

    // --- helpers de asercion para las subclases -----------------------------------------

    protected function cupoDisponible(int $salidaId = self::SALIDA_ID): int
    {
        $stmt = self::$db->prepare('SELECT cupo_disponible FROM salidas WHERE id = :id');
        $stmt->execute(['id' => $salidaId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|false */
    protected function reserva(int $id): array|false
    {
        $stmt = self::$db->prepare('SELECT * FROM reservas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    protected function contar(string $tabla, string $where = '1'): int
    {
        return (int) self::$db->query("SELECT COUNT(*) FROM {$tabla} WHERE {$where}")->fetchColumn();
    }
}
