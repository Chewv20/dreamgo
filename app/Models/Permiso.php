<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Permiso extends Model
{
    protected static string $table = 'permisos';

    /**
     * Agrupados por modulo, para renderizar la matriz de permisos ordenada.
     */
    public static function agrupadosPorModulo(): array
    {
        $permisos = self::all('modulo ASC, clave ASC');

        $agrupados = [];
        foreach ($permisos as $permiso) {
            $agrupados[$permiso['modulo']][] = $permiso;
        }

        return $agrupados;
    }
}
