<?php

namespace App\Helpers;

/**
 * Valida una URL provista por un administrador antes de guardarla para renderizarla luego en
 * un href/src. htmlspecialchars NO neutraliza el esquema javascript:, asi que un campo de URL
 * sin validar editable por un rol de contenido (contenido.gestionar / configuracion.gestionar)
 * permitiria XSS almacenado contra todos los visitantes. Misma lista blanca que
 * HtmlSanitizer::normalizarHref.
 */
final class Url
{
    /**
     * Devuelve la URL si pasa la lista blanca (http/https, ruta relativa que empieza con una
     * sola barra, mailto:, tel:, ancla #). Cualquier otra cosa (javascript:, data:, //host
     * protocolo-relativo, esquemas raros) devuelve ''.
     *
     * Rechaza tambien cualquier valor con backslash o caracteres de control: los navegadores
     * normalizan `\` -> `/`, asi que `/\host` empieza con una sola barra pero termina
     * navegando a `//host` (fuera del sitio) -- misma evasion que `//host` pero saltandose el
     * `str_starts_with('//')`.
     */
    public static function segura(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === ''
            || str_starts_with($url, '//')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $url) === 1
        ) {
            return '';
        }

        return preg_match('~^(https?://|mailto:|tel:|/|#)~i', $url) === 1 ? $url : '';
    }
}
