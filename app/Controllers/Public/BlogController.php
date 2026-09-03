<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Helpers\ArticuloJsonLd;
use App\Models\Articulo;
use Core\Controller;
use Core\Paginator;

class BlogController extends Controller
{
    private const POR_PAGINA = 9;
    private const FEED_MAX = 20;

    public function index(): void
    {
        $categorias = Articulo::categoriasConArticulos();
        $slugsValidos = array_column($categorias, 'slug');

        $categoriaPedida = (string) $this->request->query('categoria', '');
        $categoria = in_array($categoriaPedida, $slugsValidos, true) ? $categoriaPedida : null;

        $paginador = new Paginator(
            Paginator::paginaDesde($this->request),
            self::POR_PAGINA,
            Articulo::contarPublicados($categoria)
        );

        $this->view('public/blog/index', [
            'articulos' => Articulo::publicadosPaginados($paginador->porPagina, $paginador->offset(), $categoria),
            'paginador' => $paginador,
            'categorias' => $categorias,
            'categoriaActiva' => $categoria,
        ], [
            'title' => 'Blog de viajes | Dream Go Operadora Turística',
            'description' => 'Guías, consejos e inspiración para tu próximo viaje con Dream Go.',
            'feed' => ['titulo' => 'Blog de viajes - Dream Go', 'url' => '/blog/feed'],
        ]);
    }

    public function feed(): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $articulos = Articulo::publicadosPaginados(self::FEED_MAX, 0);

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->startElement('channel');
        $xml->writeElement('title', 'Blog de viajes - Dream Go');
        $xml->writeElement('link', $base . '/blog');
        $xml->writeElement('description', 'Guias, consejos e inspiracion para tu proximo viaje con Dream Go.');
        $xml->writeElement('language', 'es-mx');
        $xml->writeElement('lastBuildDate', date(DATE_RSS));

        foreach ($articulos as $a) {
            $url = $base . '/blog/' . $a['slug'];
            $fecha = $a['publicado_en'] ?? $a['creado_en'];

            $xml->startElement('item');
            $xml->writeElement('title', (string) $a['titulo']);
            $xml->writeElement('link', $url);
            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'true');
            $xml->text($url);
            $xml->endElement();
            $xml->writeElement('pubDate', date(DATE_RSS, strtotime((string) $fecha)));
            if (!empty($a['categoria_nombre'])) {
                $xml->writeElement('category', (string) $a['categoria_nombre']);
            }
            $resumen = $a['resumen'] ?: mb_substr(trim(strip_tags((string) $a['contenido'])), 0, 300);
            $xml->writeElement('description', (string) $resumen);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        $this->xml($xml->outputMemory());
    }

    public function articulo(string $slug): void
    {
        $articulo = Articulo::porSlugPublicado($slug);

        if (!$articulo) {
            $this->abort(404, 'Artículo no encontrado.');
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
            'feed' => ['titulo' => 'Blog de viajes - Dream Go', 'url' => '/blog/feed'],
        ]);
    }
}
