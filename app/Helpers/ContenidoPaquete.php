<?php

declare(strict_types=1);

namespace App\Helpers;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Conversión entre el HTML que guarda la tabla `paquetes` (descripcion_larga, itinerario,
 * incluye, no_incluye) y campos estructurados fáciles de capturar en el panel: párrafos de
 * texto, un día del itinerario por fila y un elemento de lista por línea.
 *
 * En la BD no cambia nada: se sigue guardando HTML (y la ficha pública lo pinta igual).
 * Los métodos "aEstructura" devuelven null cuando el HTML existente tiene un formato que la
 * versión estructurada no puede representar (enlaces, listas anidadas, negritas en medio
 * del texto...). En ese caso el panel muestra el editor HTML de siempre para ese campo, así
 * que convertir nunca pierde contenido.
 */
final class ContenidoPaquete
{
    // --- Listas (incluye / no incluye): un elemento por línea ---------------------------

    public static function lineasALista(string $texto): string
    {
        $items = self::lineasNoVacias($texto);
        if ($items === []) {
            return '';
        }

        return '<ul>' . implode('', array_map(static fn (string $i): string => '<li>' . self::e($i) . '</li>', $items)) . '</ul>';
    }

    /** @return string|null texto con un elemento por línea, o null si el HTML no es una lista simple */
    public static function listaALineas(?string $html): ?string
    {
        $raiz = self::parsear($html);
        if ($raiz === null) {
            return '';
        }

        $hijos = self::hijosSinEspacios($raiz);
        if (count($hijos) !== 1 || !self::esElemento($hijos[0], ['ul', 'ol'])) {
            return null;
        }

        $lineas = [];
        foreach (self::hijosSinEspacios($hijos[0]) as $li) {
            if (!self::esElemento($li, ['li']) || !self::soloTexto($li)) {
                return null;
            }
            $texto = self::normalizarEspacios($li->textContent);
            if ($texto !== '') {
                $lineas[] = $texto;
            }
        }

        return implode("\n", $lineas);
    }

    // --- Itinerario: un día por fila (título + descripción) -----------------------------

    /**
     * @param list<array{titulo: string, texto: string}> $dias
     */
    public static function diasAItinerario(array $dias): string
    {
        $html = '';
        foreach ($dias as $dia) {
            $titulo = self::normalizarEspacios($dia['titulo'] ?? '');
            $texto = self::normalizarEspacios($dia['texto'] ?? '');
            if ($titulo === '' && $texto === '') {
                continue;
            }
            $html .= '<p>'
                . ($titulo !== '' ? '<strong>' . self::e(rtrim($titulo, ':')) . ':</strong>' . ($texto !== '' ? ' ' : '') : '')
                . self::e($texto)
                . '</p>';
        }

        return $html;
    }

    /** @return list<array{titulo: string, texto: string}>|null */
    public static function itinerarioADias(?string $html): ?array
    {
        $raiz = self::parsear($html);
        if ($raiz === null) {
            return [];
        }

        $dias = [];
        foreach (self::hijosSinEspacios($raiz) as $p) {
            if (!self::esElemento($p, ['p'])) {
                return null;
            }

            $nodos = self::hijosSinEspacios($p);
            $titulo = '';
            if ($nodos !== [] && self::esElemento($nodos[0], ['strong', 'b']) && self::soloTexto($nodos[0])) {
                $titulo = rtrim(self::normalizarEspacios($nodos[0]->textContent), ':');
                array_shift($nodos);
            }

            $texto = '';
            foreach ($nodos as $nodo) {
                if (!$nodo instanceof DOMText) {
                    return null;
                }
                $texto .= $nodo->textContent;
            }
            $texto = self::normalizarEspacios(ltrim($texto, ": \t\n\r"));

            $dias[] = ['titulo' => $titulo, 'texto' => $texto];
        }

        return $dias;
    }

    // --- Descripción: párrafos separados por una línea en blanco ------------------------

    public static function textoAParrafos(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", trim($texto));
        if ($texto === '') {
            return '';
        }

        $html = '';
        foreach (preg_split('/\n\s*\n/', $texto) as $parrafo) {
            $lineas = array_map(static fn (string $l): string => self::e(trim($l)), explode("\n", trim($parrafo)));
            $html .= '<p>' . implode('<br>', $lineas) . '</p>';
        }

        return $html;
    }

    /** @return string|null texto plano con párrafos separados por línea en blanco, o null si hay otro formato */
    public static function parrafosATexto(?string $html): ?string
    {
        $raiz = self::parsear($html);
        if ($raiz === null) {
            return '';
        }

        $parrafos = [];
        foreach (self::hijosSinEspacios($raiz) as $p) {
            if (!self::esElemento($p, ['p'])) {
                return null;
            }
            $lineas = [''];
            foreach ($p->childNodes as $nodo) {
                if ($nodo instanceof DOMText) {
                    $lineas[count($lineas) - 1] .= $nodo->textContent;
                } elseif (self::esElemento($nodo, ['br'])) {
                    $lineas[] = '';
                } else {
                    return null;
                }
            }
            $parrafos[] = implode("\n", array_map([self::class, 'normalizarEspacios'], $lineas));
        }

        return implode("\n\n", $parrafos);
    }

    // --- Utilidades ------------------------------------------------------------------------

    /** Parsea un fragmento HTML; null si está vacío. */
    private static function parsear(?string $html): ?DOMElement
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $doc = new DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        foreach ($doc->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'raiz') {
                return $div;
            }
        }

        return null;
    }

    /** @return list<DOMNode> hijos que no son texto en blanco ni comentarios */
    private static function hijosSinEspacios(DOMNode $nodo): array
    {
        $hijos = [];
        foreach ($nodo->childNodes as $hijo) {
            if ($hijo instanceof DOMText && trim($hijo->textContent) === '') {
                continue;
            }
            if ($hijo->nodeType === XML_COMMENT_NODE) {
                continue;
            }
            $hijos[] = $hijo;
        }

        return $hijos;
    }

    /** @param list<string> $tags */
    private static function esElemento(DOMNode $nodo, array $tags): bool
    {
        return $nodo instanceof DOMElement && in_array(strtolower($nodo->tagName), $tags, true);
    }

    private static function soloTexto(DOMNode $nodo): bool
    {
        foreach ($nodo->childNodes as $hijo) {
            if (!$hijo instanceof DOMText) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private static function lineasNoVacias(string $texto): array
    {
        $lineas = preg_split('/\R/', $texto) ?: [];
        $lineas = array_map(static fn (string $l): string => self::normalizarEspacios(ltrim($l, "-*•· \t")), $lineas);

        return array_values(array_filter($lineas, static fn (string $l): bool => $l !== ''));
    }

    private static function normalizarEspacios(string $texto): string
    {
        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $texto));
    }

    private static function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
