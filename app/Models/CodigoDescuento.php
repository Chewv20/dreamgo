<?php

namespace App\Models;

use Core\Model;

class CodigoDescuento extends Model
{
    protected static string $table = 'codigos_descuento';

    public static function adminListado(): array
    {
        $stmt = self::db()->query(
            'SELECT cd.*, p.titulo AS paquete_titulo
             FROM codigos_descuento cd
             LEFT JOIN paquetes p ON p.id = cd.paquete_id
             ORDER BY cd.creado_en DESC'
        );

        return $stmt->fetchAll();
    }

    public static function porCodigo(string $codigo): array|false
    {
        return self::first(['codigo' => strtoupper($codigo)]);
    }
}
