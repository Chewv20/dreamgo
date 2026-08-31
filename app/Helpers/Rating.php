<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Cadena de estrellas para las reseñas. La misma lógica estaba repetida (inline) en la ficha
 * del paquete, la tarjeta del catálogo y el bloque de reseñas.
 */
final class Rating
{
    /** "★★★★☆" para un promedio redondeado a la estrella más cercana. */
    public static function estrellas(float $promedio): string
    {
        $llenas = max(0, min(5, (int) round($promedio)));

        return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
    }
}
