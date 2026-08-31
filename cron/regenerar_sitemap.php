<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Services\SitemapService;
use Core\Database;

/**
 * Auditoria 2026-08-31, hallazgo PERF-01: antes SitemapService::regenerar() corria sincrono
 * dentro de cada alta/edicion/archivado de paquete y de articulo (varias queries + escritura
 * de public/sitemap.xml), acoplando el tiempo de respuesta del panel a I/O de disco. Ahora se
 * regenera aca. Un desfase de hasta 1 h en el sitemap es inocuo para SEO (Google recrawlea a
 * su propio ritmo; lastmod es solo una pista).
 */
(new SitemapService(Database::connection()))->regenerar();

cron_log('regenerar_sitemap', 'sitemap.xml regenerado.');
