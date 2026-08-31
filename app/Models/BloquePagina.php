<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class BloquePagina extends Model
{
    protected static string $table = 'bloques_pagina';

    public static function porPagina(string $pagina): array
    {
        return self::where(['pagina' => $pagina], 'orden ASC');
    }

    public static function contenido(array $bloque): array
    {
        if (empty($bloque['contenido'])) {
            return [];
        }

        return json_decode((string) $bloque['contenido'], true) ?? [];
    }
}
