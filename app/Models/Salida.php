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
}
