<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\BloquePagina;
use App\Models\ConfiguracionSitio;

class ContenidoController extends AdminController
{
    public const PAGINAS = [
        'home' => 'Pagina de inicio',
        'nosotros' => 'Nosotros',
        'contacto' => 'Contacto',
        'destinos' => 'Destinos (listado)',
        'paquetes' => 'Paquetes (catalogo)',
    ];

    public const ETIQUETAS_BLOQUE = [
        'hero' => 'Hero (encabezado principal)',
        'ventajas' => 'Por que viajar con nosotros',
        'destinos' => 'Explora por destino',
        'paquetes_destacados' => 'Paquetes destacados',
        'testimonios' => 'Testimonios',
        'cta_final' => 'Llamado a la accion final',
        'intro' => 'Introduccion',
        'estadisticas' => 'Cifras / estadisticas',
    ];

    private const CLAVES_COLOR = [
        'color_primario',
        'color_primario_oscuro',
        'color_texto_oscuro',
        'color_fondo',
        'color_fondo_alterno',
        'color_exito',
        'color_error',
    ];

    public function index(): void
    {
        $this->view('admin/contenido/index', [
            'paginas' => self::PAGINAS,
        ], ['title' => 'Contenido del sitio | Dream Go', 'heading' => 'Contenido del sitio']);
    }

    public function pagina(string $pagina): void
    {
        if (!isset(self::PAGINAS[$pagina])) {
            $this->abort(404, 'Pagina no encontrada.');
        }

        $bloques = BloquePagina::porPagina($pagina);

        $this->view('admin/contenido/pagina', [
            'pagina' => $pagina,
            'nombrePagina' => self::PAGINAS[$pagina],
            'bloques' => $bloques,
            'etiquetas' => self::ETIQUETAS_BLOQUE,
        ], ['title' => self::PAGINAS[$pagina] . ' | Contenido | Dream Go', 'heading' => 'Contenido: ' . self::PAGINAS[$pagina]]);
    }

    public function alternarVisible(int $id): void
    {
        $this->verifyCsrf();

        $bloque = BloquePagina::find($id);
        if ($bloque !== false) {
            BloquePagina::update($id, ['visible' => $bloque['visible'] ? 0 : 1]);
            $this->redirect('/admin/contenido/' . $bloque['pagina']);
        }

        $this->redirect('/admin/contenido');
    }

    public function mover(int $id): void
    {
        $this->verifyCsrf();

        $bloque = BloquePagina::find($id);
        $direccion = (string) $this->request->input('direccion', '');

        if ($bloque !== false && in_array($direccion, ['arriba', 'abajo'], true)) {
            $lista = BloquePagina::porPagina($bloque['pagina']);
            $posicion = array_search($id, array_column($lista, 'id'), true);
            $vecino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;

            if ($posicion !== false && isset($lista[$vecino])) {
                BloquePagina::update($lista[$posicion]['id'], ['orden' => $lista[$vecino]['orden']]);
                BloquePagina::update($lista[$vecino]['id'], ['orden' => $lista[$posicion]['orden']]);
            }
        }

        if ($bloque !== false) {
            $this->redirect('/admin/contenido/' . $bloque['pagina']);
        }

        $this->redirect('/admin/contenido');
    }

    public function editarForm(int $id): void
    {
        $bloque = BloquePagina::find($id);
        if ($bloque === false) {
            $this->abort(404, 'Bloque no encontrado.');
        }

        $this->view('admin/contenido/editar', [
            'bloque' => $bloque,
            'contenido' => BloquePagina::contenido($bloque),
            'etiqueta' => self::ETIQUETAS_BLOQUE[$bloque['clave']] ?? $bloque['clave'],
        ], ['title' => 'Editar bloque | Dream Go', 'heading' => 'Editar contenido']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $bloque = BloquePagina::find($id);
        if ($bloque === false) {
            $this->abort(404, 'Bloque no encontrado.');
        }

        $colorFondo = (string) $this->request->input('color_fondo', '');

        $datos = [
            'titulo' => (string) $this->request->input('titulo', ''),
            'subtitulo' => (string) $this->request->input('subtitulo', ''),
            'color_fondo' => self::esColorHexValido($colorFondo) ? $colorFondo : null,
        ];

        $contenido = $this->construirContenido($bloque['pagina'], $bloque['clave']);
        if ($contenido !== null) {
            $datos['contenido'] = json_encode($contenido, JSON_UNESCAPED_UNICODE);
        }

        BloquePagina::update($id, $datos);

        Flash::set('exito', 'Bloque actualizado correctamente.');
        $this->redirect('/admin/contenido/' . $bloque['pagina']);
    }

    public function colores(): void
    {
        $valores = [];
        foreach (self::CLAVES_COLOR as $clave) {
            $valores[$clave] = ConfiguracionSitio::get($clave, '#000000');
        }

        $this->view('admin/contenido/colores', [
            'valores' => $valores,
        ], ['title' => 'Colores del sitio | Dream Go', 'heading' => 'Colores del sitio']);
    }

    public function guardarColores(): void
    {
        $this->verifyCsrf();

        foreach (self::CLAVES_COLOR as $clave) {
            $valor = (string) $this->request->input($clave, '');
            if (self::esColorHexValido($valor)) {
                ConfiguracionSitio::set($clave, $valor);
            }
        }

        Flash::set('exito', 'Colores actualizados correctamente.');
        $this->redirect('/admin/colores');
    }

    private static function esColorHexValido(string $valor): bool
    {
        return (bool) preg_match('/^#[0-9a-f]{6}$/i', $valor);
    }

    private function construirContenido(string $pagina, string $clave): ?array
    {
        return match (true) {
            $clave === 'hero' => [
                'cta_primario_texto' => (string) $this->request->input('cta_primario_texto', ''),
                'cta_primario_link' => (string) $this->request->input('cta_primario_link', ''),
                'cta_secundario_texto' => (string) $this->request->input('cta_secundario_texto', ''),
                'cta_secundario_link' => (string) $this->request->input('cta_secundario_link', ''),
            ],
            $clave === 'cta_final' => [
                'boton_texto' => (string) $this->request->input('boton_texto', ''),
                'boton_link' => (string) $this->request->input('boton_link', ''),
            ],
            $clave === 'ventajas' => [
                'items' => array_map(
                    fn (int $i) => [
                        'icono' => (string) $this->request->input("item_{$i}_icono", 'chat'),
                        'titulo' => (string) $this->request->input("item_{$i}_titulo", ''),
                        'texto' => (string) $this->request->input("item_{$i}_texto", ''),
                    ],
                    range(0, 3)
                ),
            ],
            $clave === 'testimonios' => [
                'items' => array_map(
                    fn (int $i) => [
                        'inicial' => (string) $this->request->input("item_{$i}_inicial", ''),
                        'texto' => (string) $this->request->input("item_{$i}_texto", ''),
                        'autor' => (string) $this->request->input("item_{$i}_autor", ''),
                    ],
                    range(0, 2)
                ),
            ],
            $clave === 'estadisticas' => [
                'items' => array_map(
                    fn (int $i) => [
                        'numero' => (string) $this->request->input("item_{$i}_numero", ''),
                        'etiqueta' => (string) $this->request->input("item_{$i}_etiqueta", ''),
                    ],
                    range(0, 2)
                ),
            ],
            $clave === 'intro' && $pagina === 'nosotros' => [
                'parrafo_2' => (string) $this->request->input('parrafo_2', ''),
            ],
            default => null,
        };
    }
}
