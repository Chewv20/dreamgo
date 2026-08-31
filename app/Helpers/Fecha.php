<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Formateo de fechas en español sin depender de la extensión intl ni de setlocale() (que es
 * global al proceso y poco fiable en Windows). Reemplaza los `date('d M Y', ...)` sueltos por
 * las vistas, que emitían el mes abreviado en inglés ("Sep", "Mar").
 */
final class Fecha
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private const MESES_ABREV = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    /** "6 sep 2026" — devuelve '' si la entrada no es una fecha válida. */
    public static function corta(?string $fecha): string
    {
        $ts = self::timestamp($fecha);
        if ($ts === null) {
            return '';
        }

        return (int) date('j', $ts) . ' ' . self::MESES_ABREV[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /** "6 sep 2026 14:30" */
    public static function cortaHora(?string $fecha): string
    {
        $ts = self::timestamp($fecha);
        if ($ts === null) {
            return '';
        }

        return self::corta($fecha) . ' ' . date('H:i', $ts);
    }

    /** "6 de septiembre de 2026" */
    public static function larga(?string $fecha): string
    {
        $ts = self::timestamp($fecha);
        if ($ts === null) {
            return '';
        }

        return (int) date('j', $ts) . ' de ' . self::MESES[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
    }

    private static function timestamp(?string $fecha): ?int
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return null;
        }

        $ts = strtotime($fecha);

        return $ts === false ? null : $ts;
    }
}
