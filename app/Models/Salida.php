<?php

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
}
