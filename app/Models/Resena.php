<?php

namespace App\Models;

use Core\Model;

class Resena extends Model
{
    protected static string $table = 'resenas';

    public static function existePara(int $reservaId): bool
    {
        return self::first(['reserva_id' => $reservaId]) !== false;
    }

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT res.*, c.nombre AS cliente_nombre, p.titulo AS paquete_titulo
             FROM resenas res
             INNER JOIN clientes c ON c.id = res.cliente_id
             INNER JOIN paquetes p ON p.id = res.paquete_id
             ORDER BY res.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM resenas')->fetchColumn();
    }

    public static function aprobadasDelPaquete(int $paqueteId, int $limite = 6): array
    {
        $stmt = self::db()->prepare(
            'SELECT res.*, c.nombre AS cliente_nombre
             FROM resenas res
             INNER JOIN clientes c ON c.id = res.cliente_id
             WHERE res.paquete_id = :paquete_id AND res.estado = "aprobada"
             ORDER BY res.creado_en DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':paquete_id', $paqueteId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
