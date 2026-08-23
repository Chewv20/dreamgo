<?php

namespace App\Controllers\Public;

use App\Models\BloquePagina;
use App\Models\Categoria;
use App\Models\Paquete;
use Core\Controller;

class DestinoController extends Controller
{
    public function index(): void
    {
        $bloques = BloquePagina::porPagina('destinos');

        $this->view('public/destinos/index', [
            'categorias' => Categoria::activas(),
            'intro' => $bloques[0] ?? null,
        ], [
            'title' => 'Destinos | Dream Go Operadora Turistica',
            'description' => 'Explora nuestros destinos nacionales e internacionales.',
        ]);
    }

    public function mostrar(string $slug): void
    {
        $categoria = Categoria::porSlug($slug);

        if (!$categoria) {
            $this->abort(404, 'Destino no encontrado.');
        }

        $paquetes = Paquete::publicadosConFiltros(['categoria' => $slug]);

        $this->view('public/destinos/mostrar', [
            'categoria' => $categoria,
            'paquetes' => $paquetes,
        ], [
            'title' => $categoria['nombre'] . ' | Dream Go Operadora Turistica',
            'description' => $categoria['descripcion'] ?? ('Paquetes y excursiones en ' . $categoria['nombre']),
        ]);
    }
}
