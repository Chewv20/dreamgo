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
     * @param array{categoria?: string, tipo?: string} $filtros
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
     * @param array{categoria?: string, tipo?: string} $filtros
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

    /**
     * @param array{categoria?: string, tipo?: string} $filtros
     * @return array{0: string, 1: array<string, string>}
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
