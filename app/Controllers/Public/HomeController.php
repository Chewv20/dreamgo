<?php

namespace App\Controllers\Public;

use App\Models\Categoria;
use App\Models\ConfiguracionSitio;
use App\Models\Paquete;
use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('public/home/index', [
            'destacados' => Paquete::destacados(3),
            'categorias' => Categoria::activas(),
        ], [
            'title' => ConfiguracionSitio::get('meta_title_default', 'Dream Go Operadora Turistica'),
            'description' => ConfiguracionSitio::get('meta_description_default', 'Excursiones y paquetes de viaje.'),
        ]);
    }
}
