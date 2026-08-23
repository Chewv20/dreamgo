<?php

namespace App\Helpers;

final class Flash
{
    private const SESSION_KEY = '_flash';

    public static function set(string $tipo, string $mensaje): void
    {
        $_SESSION[self::SESSION_KEY][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function pull(): array
    {
        $mensajes = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return $mensajes;
    }
}
