<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Añade `?v=<mtime>` a los assets versionados por nombre fijo (site.css/js, admin.css/js) para
 * poder servirlos con cache de largo plazo: al cambiar el archivo cambia el mtime y el
 * navegador (y el service worker) piden la versión nueva sin esperar a que expire la cache.
 */
final class Asset
{
    /** @var array<string, int|null> */
    private static array $cache = [];

    public static function url(string $ruta): string
    {
        if (!array_key_exists($ruta, self::$cache)) {
            $absoluta = BASE_PATH . '/public' . $ruta;
            self::$cache[$ruta] = is_file($absoluta) ? filemtime($absoluta) : null;
        }

        $version = self::$cache[$ruta];

        return $version !== null ? $ruta . '?v=' . $version : $ruta;
    }
}
