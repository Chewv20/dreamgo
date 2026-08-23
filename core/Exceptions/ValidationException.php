<?php

namespace Core\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors clave del campo => mensaje
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Los datos enviados no son validos.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
