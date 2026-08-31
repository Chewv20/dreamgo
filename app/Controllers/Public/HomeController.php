<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Models\BloquePagina;
use App\Models\Categoria;
use App\Models\ConfiguracionSitio;
use App\Models\Paquete;
use App\Models\Resena;
use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $destacados = Paquete::destacados(3);

        $this->view('public/home/index', [
            'bloques' => BloquePagina::porPagina('home'),
            'destacados' => $destacados,
            'resumenes' => Resena::resumenPorPaquetes(array_column($destacados, 'id')),
            'categorias' => Categoria::activas(),
        ], [
            'title' => ConfiguracionSitio::get('meta_title_default', 'Dream Go Operadora Turistica'),
            'description' => ConfiguracionSitio::get('meta_description_default', 'Excursiones y paquetes de viaje.'),
        ]);
    }
}
