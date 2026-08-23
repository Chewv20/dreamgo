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
    public static function publicadosConFiltros(array $filtros = []): array
    {
        $sql = 'SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
                FROM paquetes p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = "publicado"';
        $params = [];

        if (!empty($filtros['categoria'])) {
            $sql .= ' AND c.slug = :categoria';
            $params['categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= ' AND c.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }

        $sql .= ' ORDER BY p.destacado DESC, p.creado_en DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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

    public static function adminListado(): array
    {
        $stmt = self::db()->query(
            'SELECT p.*, c.nombre AS categoria_nombre
             FROM paquetes p
             INNER JOIN categorias c ON c.id = p.categoria_id
             ORDER BY p.creado_en DESC'
        );

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
