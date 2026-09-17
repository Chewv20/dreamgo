<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Modales promocionales (tabla `modales_promocion`). Se administran desde /admin/modales;
 * en el sitio publico se muestra como maximo uno por carga, elegido por prioridad.
 */
class ModalPromocion extends Model
{
    protected static string $table = 'modales_promocion';

    /**
     * Listado para el panel: todos los modales (activos e inactivos) por prioridad, con el
     * titulo y estado del paquete enlazado para avisar si dejo de estar publicado.
     */
    public static function adminListado(): array
    {
        return self::db()->query(
            'SELECT m.*, p.titulo AS paquete_titulo, p.slug AS paquete_slug, p.estado AS paquete_estado
             FROM modales_promocion m
             INNER JOIN paquetes p ON p.id = m.paquete_id
             ORDER BY m.prioridad DESC, m.id DESC'
        )->fetchAll();
    }

    /**
     * El modal a mostrar ahora mismo, o false si no hay ninguno. Filtra por:
     * activo, dentro de la ventana de fechas, paquete publicado y su destino visible, y
     * alcance (los de alcance "inicio" solo cuentan cuando $esInicio es true). Si varios
     * califican, gana el de mayor prioridad y, a igualdad, el mas reciente.
     */
    public static function vigente(bool $esInicio): array|false
    {
        $stmt = self::db()->prepare(
            'SELECT m.*, p.slug AS paquete_slug, p.titulo AS paquete_titulo
             FROM modales_promocion m
             INNER JOIN paquetes p ON p.id = m.paquete_id
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE m.activo = 1
               AND p.estado = "publicado" AND c.activo = 1
               AND (m.fecha_inicio IS NULL OR m.fecha_inicio <= CURDATE())
               AND (m.fecha_fin IS NULL OR m.fecha_fin >= CURDATE())
               AND (m.mostrar_en = "todas" OR :es_inicio = 1)
             ORDER BY m.prioridad DESC, m.id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':es_inicio', $esInicio ? 1 : 0, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
}
