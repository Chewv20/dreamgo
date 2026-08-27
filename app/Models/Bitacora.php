<?php

namespace App\Models;

use Core\Model;
use PDO;

class Bitacora extends Model
{
    protected static string $table = 'bitacora_admin';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function adminListado(int $limite = 20, int $offset = 0, ?string $accion = null): array
    {
        [$where, $params] = self::filtroAccion($accion);

        $stmt = self::db()->prepare(
            'SELECT * FROM bitacora_admin ' . $where . ' ORDER BY creado_en DESC, id DESC LIMIT :limite OFFSET :offset'
        );
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(?string $accion = null): int
    {
        [$where, $params] = self::filtroAccion($accion);

        $stmt = self::db()->prepare('SELECT COUNT(*) FROM bitacora_admin ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<string> */
    public static function accionesDistintas(): array
    {
        return self::db()
            ->query('SELECT DISTINCT accion FROM bitacora_admin ORDER BY accion')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private static function filtroAccion(?string $accion): array
    {
        if ($accion === null || $accion === '') {
            return ['', []];
        }

        return ['WHERE accion = :accion', ['accion' => $accion]];
    }
}
