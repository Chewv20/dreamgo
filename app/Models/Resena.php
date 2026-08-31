<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Resena extends Model
{
    protected static string $table = 'resenas';

    public static function existePara(int $reservaId): bool
    {
        return self::first(['reserva_id' => $reservaId]) !== false;
    }

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT res.*, c.nombre AS cliente_nombre, p.titulo AS paquete_titulo
             FROM resenas res
             INNER JOIN clientes c ON c.id = res.cliente_id
             INNER JOIN paquetes p ON p.id = res.paquete_id
             ORDER BY res.creado_en DESC
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM resenas')->fetchColumn();
    }

    /**
     * Promedio y conteo de reseñas aprobadas de un paquete.
     *
     * @return array{promedio: float, total: int}
     */
    public static function resumenPorPaquete(int $paqueteId): array
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) AS total, COALESCE(AVG(calificacion), 0) AS promedio
             FROM resenas WHERE paquete_id = :id AND estado = "aprobada"'
        );
        $stmt->execute(['id' => $paqueteId]);
        $fila = $stmt->fetch();

        return [
            'promedio' => round((float) $fila['promedio'], 1),
            'total' => (int) $fila['total'],
        ];
    }

    /**
     * Igual que resumenPorPaquete() pero en lote, para los listados de tarjetas (evita N+1).
     * Solo incluye paquetes con al menos una reseña aprobada.
     *
     * @param list<int> $paqueteIds
     * @return array<int, array{promedio: float, total: int}>
     */
    public static function resumenPorPaquetes(array $paqueteIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $paqueteIds)));
        if ($ids === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare(
            "SELECT paquete_id, COUNT(*) AS total, AVG(calificacion) AS promedio
             FROM resenas WHERE estado = 'aprobada' AND paquete_id IN ({$marcadores})
             GROUP BY paquete_id"
        );
        $stmt->execute($ids);

        $resumen = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resumen[(int) $fila['paquete_id']] = [
                'promedio' => round((float) $fila['promedio'], 1),
                'total' => (int) $fila['total'],
            ];
        }

        return $resumen;
    }

    /**
     * Nombre publico de quien deja una reseña: primer nombre + inicial del segundo token
     * ("Juan Perez" -> "Juan P."). Se muestra asi por privacidad (decidido con el usuario en
     * la primera ronda). Publico para reusarlo entre la vista y el JSON-LD.
     */
    public static function nombrePublico(string $nombreCompleto): string
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($partes === []) {
            return '';
        }

        $publico = $partes[0];
        if (isset($partes[1])) {
            $publico .= ' ' . mb_strtoupper(mb_substr($partes[1], 0, 1)) . '.';
        }

        return $publico;
    }

    public static function aprobadasDelPaquete(int $paqueteId, int $limite = 6): array
    {
        $stmt = self::db()->prepare(
            'SELECT res.*, c.nombre AS cliente_nombre
             FROM resenas res
             INNER JOIN clientes c ON c.id = res.cliente_id
             WHERE res.paquete_id = :paquete_id AND res.estado = "aprobada"
             ORDER BY res.creado_en DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':paquete_id', $paqueteId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
