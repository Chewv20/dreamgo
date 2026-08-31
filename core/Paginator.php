<?php

declare(strict_types=1);

namespace Core;

final class Paginator
{
    public readonly int $pagina;
    public readonly int $totalPaginas;

    public function __construct(
        int $pagina,
        public readonly int $porPagina,
        public readonly int $total,
    ) {
        $this->totalPaginas = max(1, (int) ceil($this->total / $this->porPagina));
        $this->pagina = max(1, min($pagina, $this->totalPaginas));
    }

    public static function paginaDesde(Request $request): int
    {
        return max(1, (int) $request->query('pagina', 1));
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    public function tieneAnterior(): bool
    {
        return $this->pagina > 1;
    }

    public function tieneSiguiente(): bool
    {
        return $this->pagina < $this->totalPaginas;
    }
}
