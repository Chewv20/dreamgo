<?php

namespace App\Models;

use Core\Model;

class Reserva extends Model
{
    protected static string $table = 'reservas';

    public static function adminListado(): array
    {
        $stmt = self::db()->query(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, p.titulo AS paquete_titulo, p.id AS paquete_id
             FROM reservas r
             INNER JOIN clientes c ON c.id = r.cliente_id
             INNER JOIN salidas s ON s.id = r.salida_id
             INNER JOIN paquetes p ON p.id = s.paquete_id
             ORDER BY r.creado_en DESC'
        );

        return $stmt->fetchAll();
    }

    public static function conDetalle(int $id): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono,
                    s.fecha_salida, s.fecha_regreso, p.titulo AS paquete_titulo
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
     * Datos para el calendario admin: salidas de un mes con conteo de reservas por estado.
     */
    public static function calendarioMes(int $anio, int $mes): array
    {
        $stmt = self::db()->prepare(
            'SELECT s.id AS salida_id, s.fecha_salida, s.cupo_maximo, s.cupo_disponible, s.estado AS salida_estado,
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
}
