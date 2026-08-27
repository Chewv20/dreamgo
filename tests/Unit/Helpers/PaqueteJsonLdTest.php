<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PaqueteJsonLd;
use App\Models\Resena;
use PHPUnit\Framework\TestCase;

final class PaqueteJsonLdTest extends TestCase
{
    private function paquete(): array
    {
        return [
            'slug' => 'cancun-todo-incluido',
            'titulo' => 'Cancún Todo Incluido',
            'resumen' => 'Playa, resort y traslados.',
            'moneda' => 'MXN',
            'precio_desde' => '12999.00',
            'imagen_portada' => '/uploads/paquetes/thumbs/cancun.jpg',
        ];
    }

    private function resenas(int $n): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = [
                'calificacion' => 5,
                'cliente_nombre' => 'Cliente Prueba',
                'comentario' => 'Excelente viaje, todo salió bien.',
                'creado_en' => '2026-07-15 10:00:00',
            ];
        }

        return $out;
    }

    public function testSinResenasNoEmiteProductNiAggregateRating(): void
    {
        $json = PaqueteJsonLd::construir($this->paquete(), ['promedio' => 0.0, 'total' => 0], [], 'https://dreamgo.test');
        $nodos = json_decode($json, true);

        $this->assertIsArray($nodos);
        $tipos = array_column($nodos, '@type');
        $this->assertContains('TouristTrip', $tipos);
        $this->assertContains('BreadcrumbList', $tipos);
        $this->assertNotContains('Product', $tipos);
        $this->assertStringNotContainsString('AggregateRating', $json);
    }

    public function testConResenasEmiteProductConRatingYReviews(): void
    {
        $json = PaqueteJsonLd::construir($this->paquete(), ['promedio' => 4.7, 'total' => 3], $this->resenas(3), 'https://dreamgo.test');
        $nodos = json_decode($json, true);

        $product = null;
        foreach ($nodos as $nodo) {
            if (($nodo['@type'] ?? null) === 'Product') {
                $product = $nodo;
            }
        }

        $this->assertNotNull($product);
        $this->assertSame('4.7', $product['aggregateRating']['ratingValue']);
        $this->assertSame(3, $product['aggregateRating']['reviewCount']);
        $this->assertCount(3, $product['review']);
        $this->assertSame('Cliente P.', $product['review'][0]['author']['name']);
        $this->assertSame('2026-07-15', $product['review'][0]['datePublished']);
        $this->assertSame('https://dreamgo.test/paquetes/cancun-todo-incluido', $product['offers']['url']);
    }

    public function testSalidaEsJsonValidoYSeguroParaScript(): void
    {
        $json = PaqueteJsonLd::construir($this->paquete(), ['promedio' => 5.0, 'total' => 1], $this->resenas(1), 'https://dreamgo.test');

        $this->assertNotNull(json_decode($json));
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
    }

    /**
     * @dataProvider nombres
     */
    public function testNombrePublico(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Resena::nombrePublico($entrada));
    }

    public static function nombres(): array
    {
        return [
            ['Juan Pérez', 'Juan P.'],
            ['Ana', 'Ana'],
            ['  María   de la Cruz  ', 'María D.'],
            ['josé lópez', 'josé L.'],
            ['', ''],
        ];
    }
}
