<?php

namespace App\Services;

use PDO;

final class SitemapService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Dominio de produccion. Se usa solo como respaldo si APP_URL no esta en el .env: sin
     * esto, un sitemap.xml regenerado en un entorno mal configurado quedaria lleno de <loc>
     * apuntando al dominio de desarrollo. Coincide con el Sitemap: hardcodeado en
     * public/robots.txt (auditoria 2026-08-23, hallazgo #16).
     */
    private const DOMINIO_PRODUCCION = 'https://dreamgooperadoraturistica.com';

    public function regenerar(): void
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? self::DOMINIO_PRODUCCION, '/');
        $urls = $this->urlsEstaticas($baseUrl);

        foreach ($this->paquetesPublicados() as $paquete) {
            $urls[] = [
                'loc' => $baseUrl . '/paquetes/' . $paquete['slug'],
                'lastmod' => date('Y-m-d', strtotime($paquete['actualizado_en'])),
                'priority' => '0.8',
            ];
        }

        foreach ($this->categoriasActivas() as $categoria) {
            $urls[] = [
                'loc' => $baseUrl . '/destinos/' . $categoria['slug'],
                'lastmod' => date('Y-m-d'),
                'priority' => '0.6',
            ];
        }

        $urls[] = ['loc' => $baseUrl . '/blog', 'lastmod' => date('Y-m-d'), 'priority' => '0.6'];

        foreach ($this->articulosPublicados() as $articulo) {
            $urls[] = [
                'loc' => $baseUrl . '/blog/' . $articulo['slug'],
                'lastmod' => date('Y-m-d', strtotime($articulo['actualizado_en'])),
                'priority' => '0.6',
            ];
        }

        $this->escribirXml($urls);
    }

    private function urlsEstaticas(string $baseUrl): array
    {
        $paginas = ['', '/destinos', '/paquetes', '/nosotros', '/contacto', '/cotizador', '/aviso-de-privacidad'];
        $hoy = date('Y-m-d');

        return array_map(static function ($p) use ($baseUrl, $hoy): array {
            $priority = $p === '' ? '1.0' : ($p === '/aviso-de-privacidad' ? '0.2' : '0.7');

            return ['loc' => $baseUrl . $p, 'lastmod' => $hoy, 'priority' => $priority];
        }, $paginas);
    }

    private function paquetesPublicados(): array
    {
        $stmt = $this->db->query("SELECT slug, actualizado_en FROM paquetes WHERE estado = 'publicado'");

        return $stmt->fetchAll();
    }

    private function categoriasActivas(): array
    {
        $stmt = $this->db->query('SELECT slug FROM categorias WHERE activo = 1');

        return $stmt->fetchAll();
    }

    private function articulosPublicados(): array
    {
        $stmt = $this->db->query("SELECT slug, actualizado_en FROM articulos WHERE estado = 'publicado'");

        return $stmt->fetchAll();
    }

    private function escribirXml(array $urls): void
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url['loc']);
            $xml->writeElement('lastmod', $url['lastmod']);
            $xml->writeElement('priority', $url['priority']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        file_put_contents(BASE_PATH . '/public/sitemap.xml', $xml->outputMemory());
    }

    // public/robots.txt NO se regenera desde aca a proposito: es un archivo estatico y
    // estable (Apache lo sirve directo, nunca pasa por PHP). La version del repo ya omite
    // "Disallow: /admin/" (auditoria 2026-08-23, hallazgo #16: no publicar la ruta del
    // panel; la no-indexacion real la da el <meta robots noindex> del layout admin).
    // Reescribirlo en cada edicion de contenido reintroducia esa linea.
}
