<?php

namespace App\Controllers\Public;

use App\Models\BloquePagina;
use App\Models\Categoria;
use App\Models\Paquete;
use Core\Controller;
use Core\Paginator;

class PaqueteController extends Controller
{
    private const POR_PAGINA = 12;

    public function catalogo(): void
    {
        $categoriaSlug = (string) $this->request->query('categoria', '');
        $tipo = (string) $this->request->query('tipo', '');

        $filtros = array_filter([
            'categoria' => $categoriaSlug,
            'tipo' => in_array($tipo, ['nacional', 'internacional'], true) ? $tipo : '',
        ]);

        $bloques = BloquePagina::porPagina('paquetes');

        $paginador = new Paginator(
            Paginator::paginaDesde($this->request),
            self::POR_PAGINA,
            Paquete::contarPublicados($filtros)
        );

        $this->view('public/paquetes/catalogo', [
            'paquetes' => Paquete::publicadosConFiltros($filtros, $paginador->porPagina, $paginador->offset()),
            'categorias' => Categoria::activas(),
            'categoriaActiva' => $categoriaSlug,
            'tipoActivo' => $tipo,
            'intro' => $bloques[0] ?? null,
            'paginador' => $paginador,
        ], [
            'title' => 'Paquetes y excursiones | Dream Go Operadora Turistica',
            'description' => 'Catalogo completo de paquetes y excursiones nacionales e internacionales.',
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

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $paquete['titulo'],
            'description' => $paquete['resumen'],
            'image' => !empty($paquete['imagen_portada']) ? (($_ENV['APP_URL'] ?? '') . $paquete['imagen_portada']) : null,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $paquete['moneda'],
                'price' => $paquete['precio_desde'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $this->view('public/paquetes/ficha', [
            'paquete' => $paquete,
            'imagenes' => $imagenes,
            'salidas' => $salidas,
        ], [
            'title' => $paquete['meta_title'] ?: ($paquete['titulo'] . ' | Dream Go Operadora Turistica'),
            'description' => $paquete['meta_description'] ?: $paquete['resumen'],
            'ogImage' => $paquete['imagen_portada'] ?? '/assets/img/logo.avif',
            'jsonLd' => $jsonLd,
        ]);
    }
}
