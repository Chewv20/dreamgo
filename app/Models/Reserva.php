<?php

namespace App\Models;

use Core\Model;

class Reserva extends Model
{
    protected static string $table = 'reservas';

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, p.titulo AS paquete_titulo, p.id AS paquete_id
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             ORDER BY r.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM reservas')->fetchColumn();
    }

    /**
     * Igual que adminListado() pero sin paginar, para exportar el listado completo a CSV.
     */
    public static function todasAdmin(): array
    {
        return self::db()->query(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, p.titulo AS paquete_titulo, p.moneda AS paquete_moneda
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             ORDER BY r.creado_en DESC'
        )->fetchAll();
    }

    public static function conDetalle(int $id): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, s.fecha_regreso, p.titulo AS paquete_titulo, p.moneda AS paquete_moneda
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    /**
     * Consulta publica: un cliente ve su reserva con su codigo + el email con el que la hizo.
     * El filtro por email (ademas del codigo unico) evita que alguien adivine codigos ajenos
     * por fuerza bruta secuencial (los codigos son correlativos: DG-2026-000001, 000002...).
     */
    public static function porCodigoYEmail(string $codigo, string $email): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email,
                    s.fecha_salida, s.fecha_regreso, p.id AS paquete_id, p.titulo AS paquete_titulo, p.slug AS paquete_slug, p.moneda AS paquete_moneda
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             WHERE r.codigo_reserva = :codigo AND c.email = :email
             LIMIT 1'
        );
        $stmt->execute(['codigo' => $codigo, 'email' => $email]);

        return $stmt->fetch();
    }

    /**
     * Descarga publica del comprobante: exige codigo + token_publico (32 hex, no correlativo).
     * El token acompaña al link en el correo de confirmacion para que no sea enumerable a
     * partir del codigo. Devuelve la misma forma que conDetalle().
     */
    public static function porCodigoYToken(string $codigo, string $token): array|false
    {
        if (strlen($token) !== 32) {
            return false;
        }

        $stmt = self::db()->prepare(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, s.fecha_regreso, p.titulo AS paquete_titulo, p.moneda AS paquete_moneda
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             WHERE r.codigo_reserva = :codigo AND r.token_publico = :token
             LIMIT 1'
        );
        $stmt->execute(['codigo' => $codigo, 'token' => $token]);

        return $stmt->fetch();
    }

    /**
     * Datos para el calendario admin: salidas de un mes con conteo de reservas por estado.
     */
    public static function calendarioMes(int $anio, int $mes): array
    {
        $stmt = self::db()->prepare(
            'SELECT s.id AS salida_id, s.paquete_id, s.fecha_salida, s.cupo_maximo, s.cupo_disponible, s.estado AS salida_estado,
                    p.titulo AS paquete_titulo,
                    SUM(CASE WHEN r.estado = "pendiente" THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN r.estado = "confirmada" THEN 1 ELSE 0 END) AS confirmadas
             FROM salidas s
             INNER JOIN paquetes p ON p.id = s.paquete_id
             LEFT JOIN reservas r ON r.salida_id = s.id
             WHERE YEAR(s.fecha_salida) = :anio AND MONTH(s.fecha_salida) = :mes
             GROUP BY s.id
             ORDER BY s.fecha_salida ASC'
        );
        $stmt->execute(['anio' => $anio, 'mes' => $mes]);

        return $stmt->fetchAll();
    }

    /**
     * Ingresos totales (reservas confirmadas) en un rango de fechas inclusivo, filtrando por
     * confirmada_en: el ingreso se realiza cuando la reserva se confirma, no cuando se crea.
     * $desde/$hasta en formato 'Y-m-d'; $hasta se extiende hasta el fin del dia para incluir
     * confirmaciones registradas con hora dentro de esa fecha.
     */
    public static function ingresosPeriodo(string $desde, string $hasta): float
    {
        $stmt = self::db()->prepare(
            "SELECT COALESCE(SUM(precio_total), 0)
             FROM reservas
             WHERE estado = 'confirmada'
               AND confirmada_en >= :desde
               AND confirmada_en < :hasta"
        );
        $stmt->execute([
            'desde' => $desde . ' 00:00:00',
            'hasta' => date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00',
        ]);

        return (float) $stmt->fetchColumn();
    }
}
