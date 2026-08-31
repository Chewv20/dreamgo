<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use PDO;

class Articulo extends Model
{
    protected static string $table = 'articulos';

    private const SELECT_PUBLICO =
        'SELECT a.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
         FROM articulos a
         LEFT JOIN categorias c ON c.id = a.categoria_id';

    /** Orden de publicación: por publicado_en y, si falta, por creado_en. */
    private const ORDEN_PUBLICO = 'ORDER BY COALESCE(a.publicado_en, a.creado_en) DESC, a.id DESC';

    public static function publicadosPaginados(int $limite, int $offset): array
    {
        $stmt = self::db()->prepare(
            self::SELECT_PUBLICO . " WHERE a.estado = 'publicado' " . self::ORDEN_PUBLICO . ' LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarPublicados(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM articulos WHERE estado = 'publicado'")->fetchColumn();
    }

    public static function porSlugPublicado(string $slug): array|false
    {
        $stmt = self::db()->prepare(self::SELECT_PUBLICO . " WHERE a.slug = :slug AND a.estado = 'publicado' LIMIT 1");
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch();
    }

    /**
     * Artículos publicados ligados a una categoría (para el cross-link desde la página del destino).
     */
    public static function publicadosDeCategoria(int $categoriaId, int $limite = 4): array
    {
        $stmt = self::db()->prepare(
            self::SELECT_PUBLICO . " WHERE a.estado = 'publicado' AND a.categoria_id = :cid " . self::ORDEN_PUBLICO . ' LIMIT :limite'
        );
        $stmt->bindValue(':cid', $categoriaId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT a.*, c.nombre AS categoria_nombre, u.nombre AS autor_nombre
             FROM articulos a
             LEFT JOIN categorias c ON c.id = a.categoria_id
             LEFT JOIN usuarios_admin u ON u.id = a.creado_por
             ORDER BY a.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM articulos')->fetchColumn();
    }
}
