<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    private array $errores = [];

    public function __construct(private readonly array $datos)
    {
    }

    public function requerido(string $campo, string $etiqueta): self
    {
        if (trim((string) ($this->datos[$campo] ?? '')) === '') {
            $this->errores[$campo] = "{$etiqueta} es obligatorio.";
        }

        return $this;
    }

    public function email(string $campo, string $etiqueta): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if ($valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->errores[$campo] = "{$etiqueta} no es un correo válido.";
        }

        return $this;
    }

    public function telefono(string $campo, string $etiqueta): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if ($valor !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $valor)) {
            $this->errores[$campo] = "{$etiqueta} no es un teléfono válido.";
        }

        return $this;
    }

    /**
     * Nombre de persona: letras (con acentos y ñ), espacios, apostrofes, puntos y guiones.
     * Rechaza URLs, HTML y simbolos, y un punto pegado entre letras ("myxa.cc"), que es como se
     * cuela un dominio; las iniciales normales llevan espacio despues del punto ("Ma. Luisa").
     */
    public function nombrePersona(string $campo, string $etiqueta): self
    {
        $valor = trim((string) ($this->datos[$campo] ?? ''));
        if ($valor === '') {
            return $this;
        }

        if (!preg_match("/^\\p{L}[\\p{L}\\p{M}\\s'’.\\-]*$/u", $valor)
            || preg_match('/\p{L}\.\p{L}{2,}/u', $valor)
            || preg_match('/\b(?:https?|www)\b/i', $valor)) {
            $this->errores[$campo] = "{$etiqueta} solo puede contener letras, espacios, apóstrofes, puntos y guiones.";
        }

        return $this;
    }

    public function maxLength(string $campo, int $max, string $etiqueta): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if (mb_strlen($valor) > $max) {
            $this->errores[$campo] = "{$etiqueta} no puede superar {$max} caracteres.";
        }

        return $this;
    }

    public function entero(string $campo, string $etiqueta): self
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor !== '' && $valor !== null && filter_var($valor, FILTER_VALIDATE_INT) === false) {
            $this->errores[$campo] = "{$etiqueta} debe ser un número.";
        }

        return $this;
    }

    public function fecha(string $campo, string $etiqueta): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if ($valor === '') {
            return $this;
        }

        // createFromFormat + comparacion de ida y vuelta: rechaza formatos distintos
        // (27/08/2026), no acolchados (2026-8-7) y fechas imposibles que strtotime aceptaria
        // desbordando (2026-02-30 -> 2 de marzo).
        $fecha = \DateTime::createFromFormat('!Y-m-d', $valor);
        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            $this->errores[$campo] = "{$etiqueta} no es una fecha válida.";
        }

        return $this;
    }

    public function enRango(string $campo, int $min, int $max, string $etiqueta): self
    {
        $valor = $this->datos[$campo] ?? '';
        if ($valor !== '' && $valor !== null && ((int) $valor < $min || (int) $valor > $max)) {
            $this->errores[$campo] = "{$etiqueta} debe estar entre {$min} y {$max}.";
        }

        return $this;
    }

    public function pasa(): bool
    {
        return $this->errores === [];
    }

    public function errores(): array
    {
        return $this->errores;
    }
}
