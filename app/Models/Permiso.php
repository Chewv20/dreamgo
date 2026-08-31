<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use PDO;

class Permiso extends Model
{
    protected static string $table = 'permisos';

    /**
     * IDs de los permisos cuyo `clave` esta en $claves. Se usa para acotar la matriz de
     * permisos a lo que el propio editor ya tiene (auditoria 2026-09, hallazgo SEG-01):
     * un rol no-sistema no puede otorgar un permiso que su editor no posee.
     *
     * @param list<string> $claves
     * @return list<int>
     */
    public static function idsPorClaves(array $claves): array
    {
        if ($claves === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($claves), '?'));
        $stmt = self::db()->prepare("SELECT id FROM permisos WHERE clave IN ({$placeholders})");
        $stmt->execute(array_values($claves));

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

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
