<?php

declare(strict_types=1);

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
             WHERE p.estado = "publicado" AND p.destacado = 1 AND c.activo = 1
             ORDER BY p.creado_en DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Criterios de orden aceptados por el catalogo publico. Clave = valor del query param
     * `?orden=`; valor = fragmento SQL de ORDER BY (nunca viene del usuario sin pasar por
     * este mapa, asi que es seguro interpolarlo).
     */
    public const ORDENES = [
        'recientes' => 'p.destacado DESC, p.creado_en DESC',
        'precio_asc' => 'p.precio_desde ASC',
        'precio_desc' => 'p.precio_desde DESC',
        'duracion_asc' => 'p.duracion_dias ASC',
        'mejor_valorados' => 'promedio_resenas DESC, p.destacado DESC',
    ];

    public const ORDEN_DEFECTO = 'recientes';

    /**
     * @param array{categoria?: string, tipo?: string, q?: string, precio_min?: int, precio_max?: int, duracion?: string} $filtros
     */
    public static function publicadosConFiltros(array $filtros = [], int $limite = 12, int $offset = 0, string $orden = self::ORDEN_DEFECTO): array
    {
        [$clausula, $params] = self::clausulaFiltrosPublicados($filtros);

        $orderBy = self::ORDENES[$orden] ?? self::ORDENES[self::ORDEN_DEFECTO];

        // Auditoria 2026-08-31, hallazgo PERF-02: antes el promedio de resenas era una
        // subconsulta correlacionada evaluada una vez por fila devuelta. Ahora se agrega una
        // sola vez en una tabla derivada y se une con LEFT JOIN (NULL = sin resenas aprobadas,
        // igual que antes). El alias promedio_resenas sigue disponible para ORDER BY.
        $sql = 'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug,
                       ra.promedio_resenas
                FROM paquetes p
                INNER JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN (
                    SELECT paquete_id, AVG(calificacion) AS promedio_resenas
                    FROM resenas
                    WHERE estado = "aprobada"
                    GROUP BY paquete_id
                ) ra ON ra.paquete_id = p.id
                WHERE ' . $clausula . '
                ORDER BY ' . $orderBy . ', p.id DESC
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
        // c.activo = 1: ocultar un destino desde el panel tambien lo saca del catalogo publico,
        // del buscador y del conteo de resultados (no solo de la lista /destinos).
        $clausula = 'p.estado = "publicado" AND c.activo = 1';
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
            // Escapamos los comodines de LIKE del termino del usuario (no es una cuestion de
            // inyeccion, el valor va parametrizado igual): sin esto, buscar "10% descuento" o
            // "todo_incluido" da coincidencias que no tienen que ver con el % o el _ literal.
            $q = str_replace(['%', '_'], ['\%', '\_'], $filtros['q']);
            $clausula .= " AND (p.titulo LIKE :q_titulo ESCAPE '\\\\' OR p.resumen LIKE :q_resumen ESCAPE '\\\\')";
            $params['q_titulo'] = '%' . $q . '%';
            $params['q_resumen'] = '%' . $q . '%';
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
             WHERE p.slug = :slug AND p.estado = "publicado" AND c.activo = 1
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
             WHERE p.slug IN (' . implode(', ', $placeholders) . ') AND p.estado = "publicado" AND c.activo = 1'
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

    /**
     * Mueve todos los paquetes de un destino a otro (reasignacion en lote). Se usa desde el
     * panel de destinos para poder vaciar un destino y luego eliminarlo, sin editar cada
     * paquete a mano. Devuelve cuantas filas se movieron.
     */
    public static function reasignarCategoria(int $desde, int $hacia): int
    {
        $stmt = self::db()->prepare('UPDATE paquetes SET categoria_id = :hacia WHERE categoria_id = :desde');
        $stmt->execute(['hacia' => $hacia, 'desde' => $desde]);

        return $stmt->rowCount();
    }

    /**
     * Otros paquetes publicados de la misma categoria, para la seccion "te puede interesar"
     * de la ficha. Excluye el paquete actual.
     */
    public static function relacionados(int $categoriaId, int $excluirId, int $limite = 3): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = "publicado" AND p.categoria_id = :categoria AND p.id <> :excluir AND c.activo = 1
             ORDER BY p.destacado DESC, p.creado_en DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':categoria', $categoriaId, \PDO::PARAM_INT);
        $stmt->bindValue(':excluir', $excluirId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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
