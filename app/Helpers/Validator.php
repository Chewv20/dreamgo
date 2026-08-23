<?php

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
            $this->errores[$campo] = "{$etiqueta} no es un correo valido.";
        }

        return $this;
    }

    public function telefono(string $campo, string $etiqueta): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if ($valor !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $valor)) {
            $this->errores[$campo] = "{$etiqueta} no es un telefono valido.";
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
            $this->errores[$campo] = "{$etiqueta} debe ser un numero.";
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
