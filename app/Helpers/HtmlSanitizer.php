<?php

namespace App\Helpers;

/**
 * Sanitizador de HTML por lista blanca para campos WYSIWYG (itinerario, incluye,
 * descripcion). Recorre el DOM y descarta toda etiqueta/atributo no permitido en
 * vez de intentar detectar patrones peligrosos con regex sobre el texto crudo
 * (un enfoque de blacklist que se puede evadir, por ejemplo, con atributos sin comillas).
 */
final class HtmlSanitizer
{
    private const TAGS_PERMITIDOS = ['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h3', 'h4', 'a', 'span'];

    /** Etiquetas cuyo contenido no debe conservarse como texto al descartarlas. */
    private const TAGS_CONTENIDO_OPACO = ['script', 'style'];

    public static function limpiar(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $documento = new \DOMDocument();
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $raiz = $documento->getElementsByTagName('div')->item(0);
        if ($raiz === null) {
            return null;
        }

        self::limpiarHijos($raiz);

        $resultado = '';
        foreach (iterator_to_array($raiz->childNodes) as $hijo) {
            $resultado .= $documento->saveHTML($hijo);
        }

        $resultado = trim($resultado);

        return $resultado === '' ? null : $resultado;
    }

    private static function limpiarHijos(\DOMNode $nodo): void
    {
        foreach (iterator_to_array($nodo->childNodes) as $hijo) {
            if ($hijo instanceof \DOMElement) {
                $tag = strtolower($hijo->tagName);

                if (in_array($tag, self::TAGS_CONTENIDO_OPACO, true)) {
                    $nodo->removeChild($hijo);
                    continue;
                }

                self::limpiarHijos($hijo);

                if (!in_array($tag, self::TAGS_PERMITIDOS, true)) {
                    while ($hijo->firstChild !== null) {
                        $nodo->insertBefore($hijo->firstChild, $hijo);
                    }
                    $nodo->removeChild($hijo);
                    continue;
                }

                self::limpiarAtributos($hijo);
                continue;
            }

            if (!($hijo instanceof \DOMText)) {
                $nodo->removeChild($hijo);
            }
        }
    }

    private static function limpiarAtributos(\DOMElement $elemento): void
    {
        $esEnlace = strtolower($elemento->tagName) === 'a';
        $href = $esEnlace ? $elemento->getAttribute('href') : null;

        foreach (iterator_to_array($elemento->attributes) as $atributo) {
            $elemento->removeAttribute($atributo->name);
        }

        if ($esEnlace) {
            $elemento->setAttribute('href', self::normalizarHref($href ?? ''));
            $elemento->setAttribute('rel', 'noopener');
        }
    }

    private static function normalizarHref(string $href): string
    {
        $href = trim($href);

        return preg_match('/^(https?:\/\/|mailto:|tel:|\/|#)/i', $href) === 1 ? $href : '#';
    }
}
