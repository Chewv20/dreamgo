<?php

namespace App\Helpers;

/**
 * IDs de GA4 y Meta Pixel, leidos de variables de entorno (.env) y validados por formato. Se
 * validan al LEER: un valor con formato invalido simplemente deja la herramienta apagada, y
 * como el ID se interpola dentro de literales JS en app/Views/partials/analytics.php, el
 * regex es la barrera contra que un valor manipulado inyecte comillas o etiquetas.
 */
final class Analytics
{
    /** ID de medicion de Google Analytics 4, p. ej. "G-ABCD1234EF". null si no esta configurado o es invalido. */
    public static function ga4Id(): ?string
    {
        $id = trim((string) ($_ENV['GA4_MEASUREMENT_ID'] ?? ''));

        return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) === 1 ? $id : null;
    }

    /** ID del pixel de Meta/Facebook (solo digitos). null si no esta configurado o es invalido. */
    public static function metaPixelId(): ?string
    {
        $id = trim((string) ($_ENV['META_PIXEL_ID'] ?? ''));

        return preg_match('/^[0-9]{6,20}$/', $id) === 1 ? $id : null;
    }

    /** true si al menos una de las dos herramientas esta configurada. */
    public static function habilitado(): bool
    {
        return self::ga4Id() !== null || self::metaPixelId() !== null;
    }
}
