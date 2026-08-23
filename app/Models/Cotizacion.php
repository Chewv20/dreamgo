<?php

namespace App\Models;

use Core\Model;

class Cotizacion extends Model
{
    protected static string $table = 'cotizaciones';

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT c.*, p.titulo AS paquete_titulo
             FROM cotizaciones c
             LEFT JOIN paquetes p ON p.id = c.paquete_id
             ORDER BY c.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM cotizaciones')->fetchColumn();
    }

    public static function recientes(int $desde = null): array
    {
        $sql = 'SELECT * FROM cotizaciones';
        $params = [];

        if ($desde !== null) {
            $sql .= ' WHERE creado_en >= :desde';
            $params['desde'] = date('Y-m-d H:i:s', $desde);
        }

        $sql .= ' ORDER BY creado_en DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
