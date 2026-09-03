<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\PaqueteJsonLd;
use App\Models\BloquePagina;
use App\Models\Categoria;
use App\Models\Paquete;
use App\Models\Resena;
use Core\Controller;
use Core\Paginator;

class PaqueteController extends Controller
{
    private const POR_PAGINA = 12;

    /** Fuente unica del limite del comparador: PHP lo aplica server-side, y se expone al JS
     *  (site.js) via data-comparar-max en el <body> del layout publico para no repetir el
     *  numero a mano en dos lenguajes. */
    public const MAX_COMPARAR = 3;

    public function catalogo(): void
    {
        $categoriaSlug = (string) $this->request->query('categoria', '');
        $tipo = (string) $this->request->query('tipo', '');
        $q = trim((string) $this->request->query('q', ''));
        $precioMin = (string) $this->request->query('precio_min', '');
        $precioMax = (string) $this->request->query('precio_max', '');
        $duracion = (string) $this->request->query('duracion', '');
        $ordenPedido = (string) $this->request->query('orden', '');
        $orden = isset(Paquete::ORDENES[$ordenPedido]) ? $ordenPedido : Paquete::ORDEN_DEFECTO;

        $filtros = array_filter([
            'categoria' => $categoriaSlug,
            'tipo' => in_array($tipo, ['nacional', 'internacional'], true) ? $tipo : '',
            'q' => mb_substr($q, 0, 120),
            'precio_min' => ctype_digit($precioMin) ? (int) $precioMin : '',
            'precio_max' => ctype_digit($precioMax) ? (int) $precioMax : '',
            'duracion' => in_array($duracion, ['1-3', '4-7', '8-14', '15+'], true) ? $duracion : '',
        ], static fn ($valor) => $valor !== '' && $valor !== null);

        $bloques = BloquePagina::porPagina('paquetes');

        $paginador = new Paginator(
            Paginator::paginaDesde($this->request),
            self::POR_PAGINA,
            Paquete::contarPublicados($filtros)
        );

        $paquetes = Paquete::publicadosConFiltros($filtros, $paginador->porPagina, $paginador->offset(), $orden);

        $this->view('public/paquetes/catalogo', [
            'paquetes' => $paquetes,
            'resumenes' => Resena::resumenPorPaquetes(array_column($paquetes, 'id')),
            'categorias' => Categoria::activas(),
            'categoriaActiva' => $categoriaSlug,
            'tipoActivo' => $tipo,
            'qActivo' => $filtros['q'] ?? '',
            'precioMinActivo' => $filtros['precio_min'] ?? '',
            'precioMaxActivo' => $filtros['precio_max'] ?? '',
            'duracionActiva' => $filtros['duracion'] ?? '',
            'ordenActivo' => $orden,
            'totalResultados' => $paginador->total,
            'intro' => $bloques[0] ?? null,
            'paginador' => $paginador,
        ], [
            'title' => 'Paquetes y excursiones | Dream Go Operadora Turística',
            'description' => 'Catálogo completo de paquetes y excursiones nacionales e internacionales.',
        ]);
    }

    public function ficha(string $slug): void
    {
        $paquete = Paquete::porSlugPublicado($slug);

        if (!$paquete) {
            $this->abort(404, 'Paquete no encontrado.');
        }

        $imagenes = Paquete::imagenes($paquete['id']);
        $salidas = Paquete::salidasFuturas($paquete['id']);
        $resenas = Resena::aprobadasDelPaquete($paquete['id']);
        $resumen = Resena::resumenPorPaquete($paquete['id']);

        $jsonLd = PaqueteJsonLd::construir($paquete, $resumen, $resenas, (string) ($_ENV['APP_URL'] ?? ''));

        $relacionados = Paquete::relacionados((int) $paquete['categoria_id'], (int) $paquete['id'], 3);

        $this->view('public/paquetes/ficha', [
            'paquete' => $paquete,
            'imagenes' => $imagenes,
            'salidas' => $salidas,
            'resenas' => $resenas,
            'resumen' => $resumen,
            'relacionados' => $relacionados,
            'resumenesRelacionados' => Resena::resumenPorPaquetes(array_column($relacionados, 'id')),
        ], [
            'title' => $paquete['meta_title'] ?: ($paquete['titulo'] . ' | Dream Go Operadora Turística'),
            'description' => $paquete['meta_description'] ?: $paquete['resumen'],
            'ogImage' => $paquete['imagen_portada'] ?? '/assets/img/logo.avif',
            'jsonLd' => $jsonLd,
        ]);
    }

    public function comparar(): void
    {
        $slugsPedidos = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', (string) $this->request->query('paquetes', ''))
        ))));
        $slugsPedidos = array_slice($slugsPedidos, 0, self::MAX_COMPARAR);

        $encontrados = $slugsPedidos !== [] ? Paquete::porSlugsPublicados($slugsPedidos) : [];
        $porSlug = array_column($encontrados, null, 'slug');

        $paquetes = array_values(array_filter(array_map(
            static fn (string $slug) => $porSlug[$slug] ?? null,
            $slugsPedidos
        )));

        $this->view('public/paquetes/comparar', [
            'paquetes' => $paquetes,
        ], [
            'title' => 'Comparar paquetes | Dream Go Operadora Turística',
            'description' => 'Compara precio, duración y detalles de nuestros paquetes de viaje.',
        ]);
    }
}
