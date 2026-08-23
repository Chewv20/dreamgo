<?php

namespace App\Models;

use Core\Model;

class Cliente extends Model
{
    protected static string $table = 'clientes';

    public static function porEmail(string $email): array|false
    {
        return self::first(['email' => $email]);
    }

    public static function encontrarOCrear(string $nombre, string $email, string $telefono): int
    {
        $existente = self::porEmail($email);
        if ($existente) {
            return (int) $existente['id'];
        }

        return self::insert(['nombre' => $nombre, 'email' => $email, 'telefono' => $telefono]);
    }
}
