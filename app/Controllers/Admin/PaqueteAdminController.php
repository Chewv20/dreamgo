<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\HtmlSanitizer;
use App\Helpers\Slugify;
use App\Helpers\Validator;
use App\Models\Categoria;
use App\Models\Paquete;
use App\Services\ImageUploadService;
use App\Services\SitemapService;
use Core\Auth;

class PaqueteAdminController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/paquetes/index', [
            'paquetes' => Paquete::adminListado(),
        ], ['title' => 'Paquetes | Dream Go', 'heading' => 'Paquetes']);
    }

    public function crearForm(): void
    {
        $this->view('admin/paquetes/create', [
            'categorias' => Categoria::all('nombre ASC'),
        ], ['title' => 'Nuevo paquete | Dream Go', 'heading' => 'Nuevo paquete']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->datosFormulario();

        if (!$this->validar($datos)) {
            $this->redirect('/admin/paquetes/crear');
        }

        $slug = $this->slugUnico($datos['titulo']);

        $id = Paquete::insert([
            'categoria_id' => (int) $datos['categoria_id'],
            'titulo' => $datos['titulo'],
            'slug' => $slug,
            'resumen' => $datos['resumen'] ?: null,
            'descripcion_larga' => HtmlSanitizer::limpiar($datos['descripcion_larga']),
            'itinerario' => HtmlSanitizer::limpiar($datos['itinerario']),
            'incluye' => HtmlSanitizer::limpiar($datos['incluye']),
            'no_incluye' => HtmlSanitizer::limpiar($datos['no_incluye']),
            'precio_desde' => $datos['precio_desde'],
            'duracion_dias' => $datos['duracion_dias'] ?: null,
            'duracion_noches' => $datos['duracion_noches'] ?: null,
            'destacado' => $datos['destacado'],
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
            'creado_por' => Auth::id(),
        ]);

        $this->procesarImagenPortada($id, $slug);
        (new SitemapService($this->db))->regenerar();

        Flash::set('exito', 'Paquete creado correctamente.');
        $this->redirect('/admin/paquetes');
    }

    public function editarForm(int $id): void
    {
        $paquete = Paquete::find($id);
        if (!$paquete) {
            $this->abort(404);
        }

        $this->view('admin/paquetes/edit', [
            'paquete' => $paquete,
            'categorias' => Categoria::all('nombre ASC'),
            'imagenes' => Paquete::imagenes($id),
        ], ['title' => 'Editar paquete | Dream Go', 'heading' => 'Editar paquete']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $paquete = Paquete::find($id);
        if (!$paquete) {
            $this->abort(404);
        }

        $datos = $this->datosFormulario();

        if (!$this->validar($datos)) {
            $this->redirect("/admin/paquetes/{$id}/editar");
        }

        $slug = $paquete['titulo'] === $datos['titulo'] ? $paquete['slug'] : $this->slugUnico($datos['titulo'], $id);

        Paquete::update($id, [
            'categoria_id' => (int) $datos['categoria_id'],
            'titulo' => $datos['titulo'],
            'slug' => $slug,
            'resumen' => $datos['resumen'] ?: null,
            'descripcion_larga' => HtmlSanitizer::limpiar($datos['descripcion_larga']),
            'itinerario' => HtmlSanitizer::limpiar($datos['itinerario']),
            'incluye' => HtmlSanitizer::limpiar($datos['incluye']),
            'no_incluye' => HtmlSanitizer::limpiar($datos['no_incluye']),
            'precio_desde' => $datos['precio_desde'],
            'duracion_dias' => $datos['duracion_dias'] ?: null,
            'duracion_noches' => $datos['duracion_noches'] ?: null,
            'destacado' => $datos['destacado'],
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
        ]);

        $this->procesarImagenPortada($id, $slug);
        (new SitemapService($this->db))->regenerar();

        Flash::set('exito', 'Paquete actualizado correctamente.');
        $this->redirect('/admin/paquetes');
    }

    public function archivar(int $id): void
    {
        $this->verifyCsrf();

        if (!Paquete::find($id)) {
            $this->abort(404);
        }

        Paquete::update($id, ['estado' => 'archivado']);
        Flash::set('exito', 'Paquete archivado.');
        $this->redirect('/admin/paquetes');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        $datos = $this->request->only([
            'categoria_id', 'titulo', 'resumen', 'descripcion_larga', 'itinerario',
            'incluye', 'no_incluye', 'precio_desde', 'duracion_dias', 'duracion_noches',
            'estado', 'meta_title', 'meta_description',
        ]);
        $datos['destacado'] = $this->request->input('destacado') ? 1 : 0;

        return $datos;
    }

    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('categoria_id', 'La categoria')
            ->requerido('titulo', 'El titulo')->maxLength('titulo', 180, 'El titulo')
            ->requerido('precio_desde', 'El precio')
            ->requerido('estado', 'El estado');

        if ($validator->pasa() && !is_numeric($datos['precio_desde'])) {
            Flash::set('error', 'El precio debe ser un numero valido.');

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
            $existente = Paquete::first(['slug' => $slug]);
            if (!$existente || (int) $existente['id'] === $ignorarId) {
                return $slug;
            }
            $intento++;
            $slug = $base . '-' . $intento;
        }
    }

    private function procesarImagenPortada(int $paqueteId, string $slug): void
    {
        $archivo = $this->request->file('imagen');
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        try {
            $rutas = (new ImageUploadService())->procesar($archivo, $slug);
        } catch (\RuntimeException $e) {
            Flash::set('error', 'La imagen no se pudo procesar: ' . $e->getMessage());

            return;
        }

        Paquete::update($paqueteId, ['imagen_portada' => $rutas['original']]);

        $stmt = $this->db->prepare(
            'INSERT INTO imagenes_paquete (paquete_id, ruta_original, ruta_thumb, alt_text, orden) VALUES (:pid, :ro, :rt, :alt, 0)'
        );
        $stmt->execute([
            'pid' => $paqueteId,
            'ro' => $rutas['original'],
            'rt' => $rutas['thumb'],
            'alt' => $slug,
        ]);
    }
}
