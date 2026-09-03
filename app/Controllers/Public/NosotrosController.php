<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Models\BloquePagina;
use Core\Controller;

class NosotrosController extends Controller
{
    public function index(): void
    {
        $this->view('public/nosotros/index', [
            'bloques' => BloquePagina::porPagina('nosotros'),
        ], [
            'title' => 'Nosotros | Dream Go Operadora Turística',
            'description' => 'Conoce al equipo detrás de Dream Go Operadora Turística.',
        ]);
    }
}
