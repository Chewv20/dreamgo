<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ArticuloJsonLd;
use PHPUnit\Framework\TestCase;

final class ArticuloJsonLdTest extends TestCase
{
    private function articulo(): array
    {
        return [
            'slug' => 'que-llevar-a-cancun',
            'titulo' => 'Qué llevar a Cancún',
            'resumen' => 'La lista definitiva para tu maleta.',
            'imagen' => '/uploads/paquetes/original/articulo-que-llevar.jpg',
            'publicado_en' => '2026-07-01 09:00:00',
            'creado_en' => '2026-06-20 12:00:00',
            'actualizado_en' => '2026-07-05 08:00:00',
        ];
    }

    public function testEmiteArticleYBreadcrumb(): void
    {
        $json = ArticuloJsonLd::construir($this->articulo(), 'https://dreamgo.test');
        $nodos = json_decode($json, true);

        $this->assertIsArray($nodos);
        $tipos = array_column($nodos, '@type');
        $this->assertSame(['Article', 'BreadcrumbList'], $tipos);

        $article = $nodos[0];
        $this->assertSame('Qué llevar a Cancún', $article['headline']);
        $this->assertSame('https://dreamgo.test/uploads/paquetes/original/articulo-que-llevar.jpg', $article['image']);
        $this->assertStringStartsWith('2026-07-01', $article['datePublished']);
        $this->assertSame('https://dreamgo.test/blog/que-llevar-a-cancun', $article['mainEntityOfPage']);

        $crumbs = $nodos[1]['itemListElement'];
        $this->assertSame('Blog', $crumbs[1]['name']);
        $this->assertSame('https://dreamgo.test/blog/que-llevar-a-cancun', $crumbs[2]['item']);
    }

    public function testUsaCreadoEnSiNoHayPublicadoEn(): void
    {
        $articulo = $this->articulo();
        $articulo['publicado_en'] = null;

        $article = json_decode(ArticuloJsonLd::construir($articulo, 'https://dreamgo.test'), true)[0];

        $this->assertStringStartsWith('2026-06-20', $article['datePublished']);
    }

    public function testSalidaEsJsonValidoYSeguroParaScript(): void
    {
        $json = ArticuloJsonLd::construir($this->articulo(), 'https://dreamgo.test');

        $this->assertNotNull(json_decode($json));
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
    }
}
