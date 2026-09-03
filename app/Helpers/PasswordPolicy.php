<?php

declare(strict_types=1);

namespace App\Helpers;

final class PasswordPolicy
{
    private const LONGITUD_MINIMA = 8;

    public static function esValida(string $password): bool
    {
        return strlen($password) >= self::LONGITUD_MINIMA
            && preg_match('/[A-Za-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }

    public static function mensaje(): string
    {
        return 'La contraseña debe tener al menos ' . self::LONGITUD_MINIMA . ' caracteres, con letras y números.';
    }
}
