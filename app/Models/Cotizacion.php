<?php

namespace App\Models;

use Core\Model;

class Cotizacion extends Model
{
    protected static string $table = 'cotizaciones';

    /**
     * Valor de $origen para filtrar solo el trafico sin utm_source (directo / organico).
     */
    public const ORIGEN_DIRECTO = '__directo__';

    public static function adminListado(int $limite = 20, int $offset = 0, ?string $origen = null): array
    {
        [$where, $params] = self::filtroOrigen($origen);

        $stmt = self::db()->prepare(
            'SELECT c.*, p.titulo AS paquete_titulo
             FROM cotizaciones c
             LEFT JOIN paquetes p ON p.id = c.paquete_id
             ' . $where . '
             ORDER BY c.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(?string $origen = null): int
    {
        [$where, $params] = self::filtroOrigen($origen);

        $stmt = self::db()->prepare('SELECT COUNT(*) FROM cotizaciones c ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{0: string, 1: array<string, string>} clausula WHERE (o '') y sus params
     */
    private static function filtroOrigen(?string $origen): array
    {
        if ($origen === null || $origen === '') {
            return ['', []];
        }

        if ($origen === self::ORIGEN_DIRECTO) {
            return ['WHERE c.utm_source IS NULL', []];
        }

        return ['WHERE c.utm_source = :origen', ['origen' => $origen]];
    }

    /**
     * Fuentes (utm_source) distintas presentes en la tabla, para poblar el filtro del panel.
     *
     * @return list<string>
     */
    public static function fuentesDistintas(): array
    {
        return self::db()
            ->query('SELECT DISTINCT utm_source FROM cotizaciones WHERE utm_source IS NOT NULL ORDER BY utm_source')
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Conteo de cotizaciones por origen en un rango de fechas, para el dashboard. El trafico
     * sin utm_source se agrupa como 'Directo'.
     *
     * @return list<array{origen: string, total: int}>
     */
    public static function porOrigenPeriodo(string $desde, string $hasta): array
    {
        $stmt = self::db()->prepare(
            "SELECT COALESCE(utm_source, 'Directo') AS origen, COUNT(*) AS total
             FROM cotizaciones
             WHERE creado_en >= :desde AND creado_en < :hasta
             GROUP BY COALESCE(utm_source, 'Directo')
             ORDER BY total DESC, origen ASC"
        );
        $stmt->execute([
            'desde' => $desde . ' 00:00:00',
            'hasta' => date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00',
        ]);

        return array_map(
            static fn (array $f): array => ['origen' => (string) $f['origen'], 'total' => (int) $f['total']],
            $stmt->fetchAll()
        );
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
