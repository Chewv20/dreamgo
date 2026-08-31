<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\IntentoAccion;

/**
 * Generaliza el mismo patron de ventana movil que Core\Auth ya usa para el login
 * (intentos_login) a cualquier accion publica sensible, via la tabla intentos_accion.
 * Auditoria 2026-08-25, hallazgos SEG-04/SEG-05/SEG-07.
 */
final class RateLimiter
{
    /**
     * accion => [max por identificador (null = no aplica), max por ip, ventana en minutos].
     *
     * - 'reservar': identificador null a proposito (limitar por el email que manda el propio
     *   formulario no frena a un atacante que rota emails inventados en cada intento; lo que
     *   importa es el volumen de POSTs desde una IP, que es el vector real del hallazgo
     *   SEG-01/SEG-05/SEG-07: vaciar cupo o probar codigos de descuento).
     * - 'reserva_consulta' y 'resena': identificador = email consultado, para frenar tanto a
     *   quien insiste contra el email de una victima como a quien rota emails desde una IP.
     * - 'suscribir': identificador null; el abuso tipico es bombardear direcciones de terceros
     *   distintas en cada intento, asi que limitar por email objetivo no ayuda.
     * - 'cotizador': identificador null (el email lo pone el propio formulario). Cada envio
     *   inserta una fila en cotizaciones y dispara un correo al equipo, asi que el limite es
     *   mas estricto que el resto de acciones publicas.
     */
    private const LIMITES = [
        'reservar' => [null, 20, 30],
        'reserva_consulta' => [8, 30, 15],
        'resena' => [8, 30, 15],
        'suscribir' => [null, 15, 30],
        'pagar_saldo' => [null, 20, 30],
        'cotizador' => [null, 10, 30],
    ];

    public static function demasiados(string $accion, ?string $identificador, string $ip): bool
    {
        [$maxIdentificador, $maxIp, $minutos] = self::LIMITES[$accion];

        if ($maxIdentificador !== null && $identificador !== null
            && IntentoAccion::contarPorIdentificador($accion, $identificador, $minutos) >= $maxIdentificador) {
            return true;
        }

        return IntentoAccion::contarPorIp($accion, $ip, $minutos) >= $maxIp;
    }

    public static function registrar(string $accion, ?string $identificador, string $ip): void
    {
        IntentoAccion::registrar($accion, $identificador, $ip);
    }
}
