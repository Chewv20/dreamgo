<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * JSON-LD de un artículo del blog: nodo Article + BreadcrumbList (Inicio > Blog > título).
 */
final class ArticuloJsonLd
{
    /**
     * @param array $articulo  fila de Articulo::porSlugPublicado()
     * @return string JSON (array de nodos) para <script type="application/ld+json">
     */
    public static function construir(array $articulo, string $appUrl): string
    {
        $appUrl = rtrim($appUrl, '/');
        $url = $appUrl . '/blog/' . rawurlencode((string) $articulo['slug']);
        $publicado = $articulo['publicado_en'] ?: $articulo['creado_en'];

        $nodos = [];

        $nodos[] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $articulo['titulo'],
            'description' => $articulo['resumen'] ?? null,
            'image' => !empty($articulo['imagen']) ? $appUrl . $articulo['imagen'] : null,
            'datePublished' => date('c', strtotime((string) $publicado)),
            'dateModified' => !empty($articulo['actualizado_en']) ? date('c', strtotime((string) $articulo['actualizado_en'])) : null,
            'author' => ['@type' => 'Organization', 'name' => 'Dream Go Operadora Turística'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Dream Go Operadora Turística'],
            'mainEntityOfPage' => $url,
        ], static fn ($v) => $v !== null);

        $nodos[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $appUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $appUrl . '/blog'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $articulo['titulo'], 'item' => $url],
            ],
        ];

        return json_encode(
            $nodos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}
