<?php

namespace App\Models;

use Core\Model;

class Paquete extends Model
{
    protected static string $table = 'paquetes';

    public static function destacados(int $limite = 3): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = "publicado" AND p.destacado = 1
             ORDER BY p.creado_en DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param array{categoria?: string, tipo?: string, q?: string, precio_min?: int, precio_max?: int, duracion?: string} $filtros
     */
    public static function publicadosConFiltros(array $filtros = [], int $limite = 12, int $offset = 0): array
    {
        [$clausula, $params] = self::clausulaFiltrosPublicados($filtros);

        $sql = 'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
                FROM paquetes p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE ' . $clausula . '
                ORDER BY p.destacado DESC, p.creado_en DESC
                LIMIT :limite OFFSET :offset';

        $stmt = self::db()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue(':' . $clave, $valor);
        }
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param array{categoria?: string, tipo?: string, q?: string, precio_min?: int, precio_max?: int, duracion?: string} $filtros
     */
    public static function contarPublicados(array $filtros = []): int
    {
        [$clausula, $params] = self::clausulaFiltrosPublicados($filtros);

        $sql = 'SELECT COUNT(*)
                FROM paquetes p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE ' . $clausula;

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private const RANGOS_DURACION = [
        '1-3' => [1, 3],
        '4-7' => [4, 7],
        '8-14' => [8, 14],
        '15+' => [15, null],
    ];

    /**
     * @param array{categoria?: string, tipo?: string, q?: string, precio_min?: int, precio_max?: int, duracion?: string} $filtros
     * @return array{0: string, 1: array<string, string|int>}
     */
    private static function clausulaFiltrosPublicados(array $filtros): array
    {
        $clausula = 'p.estado = "publicado"';
        $params = [];

        if (!empty($filtros['categoria'])) {
            $clausula .= ' AND c.slug = :categoria';
            $params['categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['tipo'])) {
            $clausula .= ' AND c.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['q'])) {
            $clausula .= ' AND (p.titulo LIKE :q_titulo OR p.resumen LIKE :q_resumen)';
            $params['q_titulo'] = '%' . $filtros['q'] . '%';
            $params['q_resumen'] = '%' . $filtros['q'] . '%';
        }

        if (isset($filtros['precio_min']) && $filtros['precio_min'] !== '' && $filtros['precio_min'] !== null) {
            $clausula .= ' AND p.precio_desde >= :precio_min';
            $params['precio_min'] = (int) $filtros['precio_min'];
        }

        if (isset($filtros['precio_max']) && $filtros['precio_max'] !== '' && $filtros['precio_max'] !== null) {
            $clausula .= ' AND p.precio_desde <= :precio_max';
            $params['precio_max'] = (int) $filtros['precio_max'];
        }

        if (!empty($filtros['duracion']) && isset(self::RANGOS_DURACION[$filtros['duracion']])) {
            [$min, $max] = self::RANGOS_DURACION[$filtros['duracion']];
            $clausula .= ' AND p.duracion_dias >= :duracion_min';
            $params['duracion_min'] = $min;

            if ($max !== null) {
                $clausula .= ' AND p.duracion_dias <= :duracion_max';
                $params['duracion_max'] = $max;
            }
        }

        return [$clausula, $params];
    }

    public static function porSlugPublicado(string $slug): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.tipo AS categoria_tipo
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.slug = :slug AND p.estado = "publicado"
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch();
    }

    /**
     * Trae varios paquetes publicados por slug de golpe, para el comparador. El orden del
     * resultado no sigue necesariamente el de $slugs (limitacion de SQL IN); el controller
     * reordena segun el orden pedido.
     */
    public static function porSlugsPublicados(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($slugs as $i => $slug) {
            $clave = "slug{$i}";
            $placeholders[] = ":{$clave}";
            $params[$clave] = $slug;
        }

        $stmt = self::db()->prepare(
            'SELECT p.*, c.nombre AS categoria_nombre
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.slug IN (' . implode(', ', $placeholders) . ') AND p.estado = "publicado"'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Si el paquete ya tiene alguna reserva, no se debe permitir cambiar su moneda: las
     * reservas no guardan su propia moneda, la heredan vía join a paquetes.moneda, y un
     * cambio retroactivo haria que montos historicos se muestren con el codigo de moneda nuevo.
     */
    public static function tieneReservas(int $paqueteId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM reservas r INNER JOIN salidas s ON s.id = r.salida_id WHERE s.paquete_id = :id'
        );
        $stmt->execute(['id' => $paqueteId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function imagenes(int $paqueteId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM imagenes_paquete WHERE paquete_id = :id ORDER BY orden ASC'
        );
        $stmt->execute(['id' => $paqueteId]);

        return $stmt->fetchAll();
    }

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.*, c.nombre AS categoria_nombre
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             ORDER BY p.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM paquetes')->fetchColumn();
    }

    public static function salidasFuturas(int $paqueteId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM salidas
             WHERE paquete_id = :id AND estado = "abierta" AND fecha_salida >= CURDATE()
             ORDER BY fecha_salida ASC'
        );
        $stmt->execute(['id' => $paqueteId]);

        return $stmt->fetchAll();
    }
}
