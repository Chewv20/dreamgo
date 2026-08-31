<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\HtmlSanitizer;
use App\Helpers\Slugify;
use App\Helpers\Validator;
use App\Models\Articulo;
use App\Models\Categoria;
use App\Services\ImageUploadService;
use App\Services\SitemapService;
use Core\Auth;

class ArticuloAdminController extends AdminController
{
    private const ESTADOS = ['borrador' => 'Borrador', 'publicado' => 'Publicado', 'archivado' => 'Archivado'];

    public function index(): void
    {
        $paginador = $this->paginar(Articulo::contarTotal());

        $this->view('admin/articulos/index', [
            'articulos' => Articulo::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Blog | Dream Go', 'heading' => 'Articulos del blog']);
    }

    public function crearForm(): void
    {
        $this->view('admin/articulos/create', [
            'categorias' => Categoria::all('nombre ASC'),
            'estados' => self::ESTADOS,
        ], ['title' => 'Nuevo articulo | Dream Go', 'heading' => 'Nuevo articulo']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect('/admin/articulos/crear');
        }

        $slug = $this->slugUnico($datos['titulo']);

        $id = Articulo::insert([
            'titulo' => $datos['titulo'],
            'slug' => $slug,
            'resumen' => $datos['resumen'] ?: null,
            'contenido' => HtmlSanitizer::limpiar($datos['contenido']),
            'categoria_id' => $datos['categoria_id'] ?: null,
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
            'publicado_en' => $datos['estado'] === 'publicado' ? date('Y-m-d H:i:s') : null,
            'creado_por' => Auth::id(),
        ]);

        $this->procesarImagen($id, $slug);
        (new SitemapService($this->db))->regenerar();
        Auditoria::registrar('articulo.crear', 'articulo', $id, $datos['titulo'] . ' (' . $datos['estado'] . ')');

        Flash::set('exito', 'Articulo creado correctamente.');
        $this->redirect('/admin/articulos');
    }

    public function editarForm(int $id): void
    {
        $articulo = $this->encontrarO404(Articulo::class, $id);

        $this->view('admin/articulos/edit', [
            'articulo' => $articulo,
            'categorias' => Categoria::all('nombre ASC'),
            'estados' => self::ESTADOS,
        ], ['title' => 'Editar articulo | Dream Go', 'heading' => 'Editar articulo']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $articulo = $this->encontrarO404(Articulo::class, $id);

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect("/admin/articulos/{$id}/editar");
        }

        $slug = $articulo['titulo'] === $datos['titulo'] ? $articulo['slug'] : $this->slugUnico($datos['titulo'], $id);

        // publicado_en se fija la primera vez que pasa a 'publicado' y no se vuelve a tocar.
        $publicadoEn = $articulo['publicado_en'];
        if ($datos['estado'] === 'publicado' && $publicadoEn === null) {
            $publicadoEn = date('Y-m-d H:i:s');
        }

        Articulo::update($id, [
            'titulo' => $datos['titulo'],
            'slug' => $slug,
            'resumen' => $datos['resumen'] ?: null,
            'contenido' => HtmlSanitizer::limpiar($datos['contenido']),
            'categoria_id' => $datos['categoria_id'] ?: null,
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
            'publicado_en' => $publicadoEn,
        ]);

        $this->procesarImagen($id, $slug);
        (new SitemapService($this->db))->regenerar();
        Auditoria::registrar('articulo.editar', 'articulo', $id, $datos['titulo'] . ' (' . $datos['estado'] . ')');

        Flash::set('exito', 'Articulo actualizado correctamente.');
        $this->redirect('/admin/articulos');
    }

    public function archivar(int $id): void
    {
        $this->verifyCsrf();

        $articulo = $this->encontrarO404(Articulo::class, $id);

        Articulo::update($id, ['estado' => 'archivado']);
        (new SitemapService($this->db))->regenerar();
        Auditoria::registrar('articulo.archivar', 'articulo', $id, (string) ($articulo['titulo'] ?? ''));

        Flash::set('exito', 'Articulo archivado.');
        $this->redirect('/admin/articulos');
    }

    /** @return array<string, mixed> */
    private function datosFormulario(): array
    {
        return $this->request->only([
            'titulo', 'resumen', 'contenido', 'categoria_id', 'estado', 'meta_title', 'meta_description',
        ]);
    }

    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('titulo', 'El titulo')->maxLength('titulo', 180, 'El titulo')
            ->maxLength('resumen', 300, 'El resumen')
            ->requerido('estado', 'El estado');

        if ($validator->pasa() && !array_key_exists($datos['estado'] ?? '', self::ESTADOS)) {
            Flash::set('error', 'Selecciona un estado valido.');

            return false;
        }

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');

            return false;
        }

        return true;
    }

    private function slugUnico(string $titulo, ?int $ignorarId = null): string
    {
        $base = Slugify::generar($titulo);
        $slug = $base;
        $intento = 1;

        while (true) {
            $existente = Articulo::first(['slug' => $slug]);
            if (!$existente || (int) $existente['id'] === $ignorarId) {
                return $slug;
            }
            $intento++;
            $slug = $base . '-' . $intento;
        }
    }

    private function procesarImagen(int $articuloId, string $slug): void
    {
        $archivo = $this->request->file('imagen');
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        try {
            $rutas = (new ImageUploadService())->procesar($archivo, $slug, 'articulos');
        } catch (\RuntimeException $e) {
            Flash::set('error', 'La imagen no se pudo procesar: ' . $e->getMessage());

            return;
        }

        Articulo::update($articuloId, ['imagen' => $rutas['original']]);
    }
}
