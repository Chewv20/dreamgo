<?php

declare(strict_types=1);

namespace App\Helpers;

final class Slugify
{
    /**
     * Transliteracion explicita de los caracteres latinos acentuados mas comunes.
     *
     * Auditoria 2026-08-31, hallazgo SEG-08: antes se usaba iconv('UTF-8', 'ASCII//TRANSLIT'),
     * cuyo resultado depende del LC_CTYPE del sistema operativo (en Windows con locale "C"
     * convierte 'ó' en "'o", metiendo un apostrofo que ensucia el slug). El mapa fijo da el
     * mismo resultado en cualquier entorno; lo que no este en el mapa (cirilico, CJK, emojis)
     * lo colapsa el preg_replace de abajo, y si no queda nada se usa $fallback.
     */
    private const MAPA = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'ý' => 'y', 'ÿ' => 'y',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c',
        'ß' => 'ss', 'æ' => 'ae', 'Æ' => 'ae', 'œ' => 'oe', 'Œ' => 'oe',
    ];

    /**
     * Genera un slug ASCII a partir de $texto. Nunca devuelve cadena vacia (un slug '' hace
     * la ficha `/paquetes/{slug}` inalcanzable y rompe la deteccion de duplicados de
     * slugUnico()); el llamador pasa un $fallback con sentido ('paquete', 'articulo').
     */
    public static function generar(string $texto, string $fallback = 'item'): string
    {
        $texto = strtr($texto, self::MAPA);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $slug = trim((string) $texto, '-');

        return $slug !== '' ? $slug : $fallback;
    }
}
