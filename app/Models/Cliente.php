<?php

namespace App\Models;

use Core\Model;
use PDOException;

class Cliente extends Model
{
    protected static string $table = 'clientes';

    public static function porEmail(string $email): array|false
    {
        return self::first(['email' => $email]);
    }

    /**
     * El SELECT y el INSERT no son atomicos, asi que dos reservas concurrentes
     * del mismo cliente nuevo pueden llegar aqui al mismo tiempo. La restriccion
     * UNIQUE en clientes.email es la que realmente evita el duplicado; si el
     * INSERT pierde esa carrera, se recupera el id que gano en vez de fallar.
     */
    public static function encontrarOCrear(string $nombre, string $email, string $telefono): int
    {
        $existente = self::porEmail($email);
        if ($existente) {
            return (int) $existente['id'];
        }

        try {
            return self::insert(['nombre' => $nombre, 'email' => $email, 'telefono' => $telefono]);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            $existente = self::porEmail($email);
            if ($existente) {
                return (int) $existente['id'];
            }

            throw $e;
        }
    }
}
