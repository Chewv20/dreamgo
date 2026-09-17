<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Base comun para las tablas de galeria (imagenes_paquete, imagenes_destino): misma forma,
 * solo cambia el nombre de la columna que apunta al padre (paquete_id / categoria_id). Evita
 * duplicar la logica de listar/agregar/reordenar/verificar pertenencia en cada modelo padre.
 */
abstract class GaleriaImagen extends Model
{
    protected static string $columnaPadre;

    public static function paraPadre(int $padreId): array
    {
        return self::where([static::$columnaPadre => $padreId], 'orden ASC');
    }

    /**
     * Trae las imagenes de varios padres en una sola consulta (evita N+1 en listados) y las
     * agrupa por id de padre, ya ordenadas.
     *
     * @param list<int> $padreIds
     * @return array<int, list<array<string, mixed>>>
     */
    public static function paraPadres(array $padreIds): array
    {
        if ($padreIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($padreIds), '?'));
        $columna = static::$columnaPadre;
        $stmt = self::db()->prepare(
            'SELECT * FROM ' . static::$table . " WHERE {$columna} IN ({$placeholders}) ORDER BY {$columna} ASC, orden ASC"
        );
        $stmt->execute(array_values($padreIds));

        $agrupado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $agrupado[(int) $fila[$columna]][] = $fila;
        }

        return $agrupado;
    }

    public static function agregar(int $padreId, string $rutaOriginal, string $rutaThumb, ?string $altText): int
    {
        $siguienteOrden = self::paraPadre($padreId);
        $orden = $siguienteOrden === [] ? 0 : ((int) end($siguienteOrden)['orden']) + 1;

        return self::insert([
            static::$columnaPadre => $padreId,
            'ruta_original' => $rutaOriginal,
            'ruta_thumb' => $rutaThumb,
            'alt_text' => $altText,
            'orden' => $orden,
        ]);
    }

    /** Evita que un id de imagen ajeno a $padreId se pueda borrar/mover manipulando la URL. */
    public static function pertenece(int $imagenId, int $padreId): bool
    {
        $imagen = self::find($imagenId);

        return $imagen !== false && (int) $imagen[static::$columnaPadre] === $padreId;
    }

    /** Intercambia el `orden` de $imagenId con su vecino ('arriba' o 'abajo') dentro de $padreId. */
    public static function mover(int $imagenId, int $padreId, string $direccion): void
    {
        $lista = self::paraPadre($padreId);
        $posicion = array_search($imagenId, array_map('intval', array_column($lista, 'id')), true);

        if ($posicion === false) {
            return;
        }

        $vecino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;
        if (!isset($lista[$vecino])) {
            return;
        }

        self::update((int) $lista[$posicion]['id'], ['orden' => $lista[$vecino]['orden']]);
        self::update((int) $lista[$vecino]['id'], ['orden' => $lista[$posicion]['orden']]);
    }
}
