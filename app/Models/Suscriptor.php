<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Suscriptor extends Model
{
    protected static string $table = 'suscriptores';

    public static function porEmail(string $email): array|false
    {
        return self::first(['email' => $email]);
    }

    public static function porToken(string $token): array|false
    {
        return self::first(['token' => $token]);
    }

    public static function confirmados(): array
    {
        return self::where(['estado' => 'confirmado'], 'creado_en ASC');
    }

    public static function adminListado(int $limite = 20, int $offset = 0): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM suscriptores ORDER BY creado_en DESC LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function contarTotal(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM suscriptores')->fetchColumn();
    }

    /**
     * Igual que adminListado() pero sin paginar, para exportar el listado completo a CSV.
     */
    public static function todosAdmin(): array
    {
        return self::db()->query('SELECT * FROM suscriptores ORDER BY creado_en DESC')->fetchAll();
    }
}
