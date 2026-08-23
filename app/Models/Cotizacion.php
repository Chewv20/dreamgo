<?php

namespace App\Models;

use Core\Model;

class Cotizacion extends Model
{
    protected static string $table = 'cotizaciones';

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
