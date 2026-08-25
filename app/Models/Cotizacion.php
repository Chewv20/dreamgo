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

    /**
     * Igual que adminListado() pero sin paginar, para exportar el listado completo a CSV.
     */
    public static function todasAdmin(): array
    {
        return self::db()->query(
            'SELECT c.*, p.titulo AS paquete_titulo
             FROM cotizaciones c
             LEFT JOIN paquetes p ON p.id = c.paquete_id
             ORDER BY c.creado_en DESC'
        )->fetchAll();
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

    /**
     * Tasa de conversion cotizacion -> reserva en un rango de fechas, aproximada como
     * COUNT(estado='convertida') / COUNT(*) sobre cotizaciones creadas en el periodo. No es un
     * join real con reservas: no existe trazabilidad automatica entre cotizacion y la reserva
     * que origino (ver MEJORAS.md); "convertida" se marca a mano por el admin.
     *
     * @return array{total: int, convertidas: int, tasa: float} tasa en porcentaje (0-100)
     */
    public static function tasaConversionPeriodo(string $desde, string $hasta): array
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'convertida' THEN 1 ELSE 0 END) AS convertidas
             FROM cotizaciones
             WHERE creado_en >= :desde
               AND creado_en < :hasta"
        );
        $stmt->execute([
            'desde' => $desde . ' 00:00:00',
            'hasta' => date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00',
        ]);
        $fila = $stmt->fetch();

        $total = (int) $fila['total'];
        $convertidas = (int) $fila['convertidas'];

        return [
            'total' => $total,
            'convertidas' => $convertidas,
            'tasa' => $total > 0 ? round($convertidas / $total * 100, 1) : 0.0,
        ];
    }
}
