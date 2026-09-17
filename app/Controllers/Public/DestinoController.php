<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Models\Articulo;
use App\Models\BloquePagina;
use App\Models\Categoria;
use App\Models\ImagenDestino;
use App\Models\ImagenPaquete;
use App\Models\Paquete;
use App\Models\Resena;
use Core\Controller;

class DestinoController extends Controller
{
    public function index(): void
    {
        $bloques = BloquePagina::porPagina('destinos');
        $categorias = Categoria::activas();

        $this->view('public/destinos/index', [
            'categorias' => $categorias,
            'imagenes' => ImagenDestino::paraPadres(array_column($categorias, 'id')),
            'intro' => $bloques[0] ?? null,
        ], [
            'title' => 'Destinos | Dream Go Operadora Turística',
            'description' => 'Explora nuestros destinos nacionales e internacionales.',
        ]);
    }

    public function mostrar(string $slug): void
    {
        $categoria = Categoria::porSlug($slug);

        // Un destino oculto desde el panel (activo = 0) no debe ser accesible ni por URL directa,
        // igual que ya no aparece en la lista /destinos ni en el sitemap.
        if (!$categoria || (int) $categoria['activo'] !== 1) {
            $this->abort(404, 'Destino no encontrado.');
        }

        $paquetes = Paquete::publicadosConFiltros(['categoria' => $slug]);

        $this->view('public/destinos/mostrar', [
            'categoria' => $categoria,
            'paquetes' => $paquetes,
            'resumenes' => Resena::resumenPorPaquetes(array_column($paquetes, 'id')),
            'galerias' => ImagenPaquete::paraPadres(array_column($paquetes, 'id')),
            'imagenesDestino' => Categoria::imagenes((int) $categoria['id']),
            'articulos' => Articulo::publicadosDeCategoria((int) $categoria['id']),
        ], [
            'title' => $categoria['nombre'] . ' | Dream Go Operadora Turística',
            'description' => $categoria['descripcion'] ?? ('Paquetes y excursiones en ' . $categoria['nombre']),
        ]);
    }
}
