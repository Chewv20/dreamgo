<?php

namespace App\Controllers\Public;

use App\Helpers\ArticuloJsonLd;
use App\Models\Articulo;
use Core\Controller;
use Core\Paginator;

class BlogController extends Controller
{
    private const POR_PAGINA = 9;

    public function index(): void
    {
        $paginador = new Paginator(
            Paginator::paginaDesde($this->request),
            self::POR_PAGINA,
            Articulo::contarPublicados()
        );

        $this->view('public/blog/index', [
            'articulos' => Articulo::publicadosPaginados($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], [
            'title' => 'Blog de viajes | Dream Go Operadora Turistica',
            'description' => 'Guias, consejos e inspiracion para tu proximo viaje con Dream Go.',
        ]);
    }

    public function articulo(string $slug): void
    {
        $articulo = Articulo::porSlugPublicado($slug);

        if (!$articulo) {
            $this->abort(404, 'Articulo no encontrado.');
        }

        $this->view('public/blog/articulo', [
            'articulo' => $articulo,
            'relacionados' => $articulo['categoria_id'] !== null
                ? array_values(array_filter(
                    Articulo::publicadosDeCategoria((int) $articulo['categoria_id'], 4),
                    static fn (array $a): bool => (int) $a['id'] !== (int) $articulo['id']
                ))
                : [],
        ], [
            'title' => $articulo['meta_title'] ?: ($articulo['titulo'] . ' | Blog Dream Go'),
            'description' => $articulo['meta_description'] ?: $articulo['resumen'],
            'ogImage' => $articulo['imagen'] ?? '/assets/img/logo.avif',
            'jsonLd' => ArticuloJsonLd::construir($articulo, (string) ($_ENV['APP_URL'] ?? '')),
        ]);
    }
}
