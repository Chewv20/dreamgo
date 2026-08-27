<?php

namespace App\Services;

use PDO;

final class SitemapService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function regenerar(): void
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://dreamgo.local', '/');
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

        $this->escribirXml($urls);
        $this->escribirRobotsTxt($baseUrl);
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

    private function escribirRobotsTxt(string $baseUrl): void
    {
        $contenido = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /uploads/\n\nSitemap: {$baseUrl}/sitemap.xml\n";
        file_put_contents(BASE_PATH . '/public/robots.txt', $contenido);
    }
}
