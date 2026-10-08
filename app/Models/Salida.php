<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Salida extends Model
{
    protected static string $table = 'salidas';

    public static function delPaquete(int $paqueteId): array
    {
        return self::where(['paquete_id' => $paqueteId], 'fecha_salida ASC');
    }

    /**
     * Ocupacion de las proximas salidas (futuras, no canceladas), con el titulo del paquete,
     * para la tarjeta de ocupacion del dashboard. Limite fijo para no sobrecargar la tarjeta.
     */
    public static function proximasConOcupacion(int $limite = 12): array
    {
        $stmt = self::db()->prepare(
            'SELECT s.id, s.fecha_salida, s.cupo_maximo, s.cupo_disponible, p.titulo AS paquete_titulo
             FROM salidas s
             INNER JOIN paquetes p ON p.id = s.paquete_id
             WHERE s.fecha_salida >= CURDATE() AND s.estado <> "cancelada"
             ORDER BY s.fecha_salida ASC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Salidas futuras abiertas y con cupo, con su paquete: opciones para crear una reserva
     * manual desde el listado de reservas sin tener que entrar al paquete.
     */
    public static function abiertasParaReservar(): array
    {
        return self::db()->query(
            'SELECT s.id, s.fecha_salida, s.fecha_regreso, s.cupo_disponible, p.titulo AS paquete_titulo
             FROM salidas s
             INNER JOIN paquetes p ON p.id = s.paquete_id
             WHERE s.fecha_salida >= CURDATE() AND s.estado = "abierta" AND s.cupo_disponible > 0
               AND p.estado = "publicado"
             ORDER BY p.titulo ASC, s.fecha_salida ASC'
        )->fetchAll();
    }
}
