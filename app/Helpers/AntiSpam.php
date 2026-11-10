<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Filtro barato contra bots en los formularios publicos que disparan correos (cotizador y
 * contacto). Dos senales, invisibles para una persona:
 *
 * - Honeypot: un campo "sitio_web" oculto con CSS que una persona nunca ve ni llena, pero los
 *   bots que rellenan todos los inputs si.
 * - Tiempo minimo: al pintar el formulario se guarda la hora en sesion; un envio que llega en
 *   menos de MIN_SEGUNDOS (o sin haber pintado el formulario) no lo hizo una persona.
 *
 * Motivo (2026-11-10): llego una cotizacion con un enlace de spam en el nombre y un correo
 * desechable, es decir, un bot probando si el acuse al cliente sirve para mandar spam a terceros
 * desde el dominio de Dream Go.
 */
final class AntiSpam
{
    public const CAMPO_HONEYPOT = 'sitio_web';
    public const MIN_SEGUNDOS = 3;
    private const SESSION_KEY = '_antispam_ts';

    /** HTML a incluir dentro del <form>. Registra la hora en que se pinto el formulario. */
    public static function campos(string $formulario): string
    {
        $_SESSION[self::SESSION_KEY][$formulario] = time();

        return '<div class="sr-only" aria-hidden="true">'
            . '<label for="' . self::CAMPO_HONEYPOT . '">No llenes este campo</label>'
            . '<input type="text" id="' . self::CAMPO_HONEYPOT . '" name="' . self::CAMPO_HONEYPOT . '" value="" tabindex="-1" autocomplete="off">'
            . '</div>';
    }

    /** true si se lleno el campo oculto: casi seguro un bot. */
    public static function honeypotLleno(?string $valor): bool
    {
        return trim((string) $valor) !== '';
    }

    /** true si el envio llego demasiado rapido o sin que se haya pintado el formulario. */
    public static function demasiadoRapido(string $formulario, ?int $ahora = null): bool
    {
        $pintado = $_SESSION[self::SESSION_KEY][$formulario] ?? null;
        if (!is_int($pintado)) {
            return true;
        }

        return (($ahora ?? time()) - $pintado) < self::MIN_SEGUNDOS;
    }
}
