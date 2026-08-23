<?php

namespace App\Helpers;

/**
 * Sanitizador de HTML por lista blanca para campos WYSIWYG (itinerario, incluye,
 * descripcion). Evita XSS almacenado sin depender de una libreria pesada.
 */
final class HtmlSanitizer
{
    private const TAGS_PERMITIDOS = '<p><br><strong><b><em><i><ul><ol><li><h3><h4><a><span>';

    public static function limpiar(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $limpio = strip_tags($html, self::TAGS_PERMITIDOS);

        // Elimina atributos peligrosos (onclick, onerror, javascript:, etc.) conservando href simple.
        $limpio = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $limpio) ?? $limpio;
        $limpio = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', 'href="#"', $limpio) ?? $limpio;

        return $limpio;
    }
}
