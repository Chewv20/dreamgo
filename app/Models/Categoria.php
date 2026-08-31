<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Categoria extends Model
{
    protected static string $table = 'categorias';

    public static function activas(): array
    {
        return self::where(['activo' => 1], 'orden ASC');
    }

    public static function porSlug(string $slug): array|false
    {
        return self::first(['slug' => $slug]);
    }
}
