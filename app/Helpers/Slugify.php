<?php

declare(strict_types=1);

namespace App\Helpers;

final class Slugify
{
    public static function generar(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);

        return trim((string) $texto, '-');
    }
}
