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
     */
    public static function segura(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || str_starts_with($url, '//')) {
            return '';
        }

        return preg_match('~^(https?://|mailto:|tel:|/|#)~i', $url) === 1 ? $url : '';
    }
}
