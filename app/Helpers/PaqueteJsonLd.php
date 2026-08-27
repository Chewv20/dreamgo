<?php

namespace App\Helpers;

use App\Models\Resena;

/**
 * Arma el bloque JSON-LD de la ficha de un paquete: TouristTrip + BreadcrumbList siempre, y
 * un nodo Product con AggregateRating/Review cuando el paquete tiene reseñas aprobadas
 * (Google no muestra el rich snippet de estrellas sobre TouristTrip, solo sobre tipos como
 * Product, de ahi el nodo extra).
 */
final class PaqueteJsonLd
{
    /**
     * @param array $paquete  fila de Paquete::porSlugPublicado()
     * @param array{promedio: float, total: int} $resumen  Resena::resumenPorPaquete()
     * @param array $resenas  Resena::aprobadasDelPaquete() (para el detalle de cada Review)
     * @return string JSON (array de nodos) listo para <script type="application/ld+json">
     */
    public static function construir(array $paquete, array $resumen, array $resenas, string $appUrl): string
    {
        $appUrl = rtrim($appUrl, '/');
        $urlPaquete = $appUrl . '/paquetes/' . rawurlencode((string) $paquete['slug']);
        $imagen = !empty($paquete['imagen_portada']) ? $appUrl . $paquete['imagen_portada'] : null;

        $nodos = [];

        $nodos[] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $paquete['titulo'],
            'description' => $paquete['resumen'] ?? null,
            'image' => $imagen,
            'url' => $urlPaquete,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $paquete['moneda'],
                'price' => (string) $paquete['precio_desde'],
                'url' => $urlPaquete,
            ],
        ], static fn ($v) => $v !== null);

        if (($resumen['total'] ?? 0) > 0) {
            $reviews = [];
            foreach ($resenas as $r) {
                $reviews[] = [
                    '@type' => 'Review',
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int) $r['calificacion'],
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ],
                    'author' => [
                        '@type' => 'Person',
                        'name' => Resena::nombrePublico((string) $r['cliente_nombre']),
                    ],
                    'reviewBody' => (string) $r['comentario'],
                    'datePublished' => date('Y-m-d', strtotime((string) $r['creado_en'])),
                ];
            }

            $nodos[] = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $paquete['titulo'],
                'description' => $paquete['resumen'] ?? null,
                'image' => $imagen,
                'brand' => ['@type' => 'Organization', 'name' => 'Dream Go Operadora Turistica'],
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => $paquete['moneda'],
                    'price' => (string) $paquete['precio_desde'],
                    'availability' => 'https://schema.org/InStock',
                    'url' => $urlPaquete,
                ],
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (string) $resumen['promedio'],
                    'reviewCount' => (int) $resumen['total'],
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'review' => $reviews !== [] ? $reviews : null,
            ], static fn ($v) => $v !== null);
        }

        $nodos[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $appUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Paquetes', 'item' => $appUrl . '/paquetes'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $paquete['titulo'], 'item' => $urlPaquete],
            ],
        ];

        return json_encode(
            $nodos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}
