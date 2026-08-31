<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\Slugify;
use App\Helpers\Validator;
use App\Models\Categoria;
use App\Services\ImageUploadService;

/**
 * CRUD de destinos (tabla `categorias`). Hasta ahora solo se poblaban por SQL / seed_demo.sql;
 * este panel los hace administrables (crear, editar, ordenar, ocultar, eliminar) con el mismo
 * patron que paquetes y blog: slug unico, imagen de portada reencodeada y bitacora.
 */
class DestinoAdminController extends AdminController
{
    private const TIPOS = ['nacional' => 'Nacional', 'internacional' => 'Internacional'];

    public function index(): void
    {
        $this->view('admin/destinos/index', [
            'destinos' => Categoria::adminListado(),
            'tipos' => self::TIPOS,
        ], ['title' => 'Destinos | Dream Go', 'heading' => 'Destinos']);
    }

    public function crearForm(): void
    {
        $this->view('admin/destinos/create', [
            'tipos' => self::TIPOS,
        ], ['title' => 'Nuevo destino | Dream Go', 'heading' => 'Nuevo destino']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect('/admin/destinos/crear');
        }

        $slug = $this->slugUnico($datos['nombre']);

        $id = Categoria::insert([
            'nombre' => $datos['nombre'],
            'slug' => $slug,
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'] ?: null,
            'orden' => Categoria::siguienteOrden(),
            'activo' => $datos['activo'],
        ]);

        $this->procesarImagen($id, $slug);
        Auditoria::registrar('destino.crear', 'destino', $id, $datos['nombre'] . ' (' . $datos['tipo'] . ')');

        Flash::set('exito', 'Destino creado correctamente.');
        $this->redirect('/admin/destinos');
    }

    public function editarForm(int $id): void
    {
        $this->view('admin/destinos/edit', [
            'destino' => $this->encontrarO404(Categoria::class, $id),
            'tipos' => self::TIPOS,
        ], ['title' => 'Editar destino | Dream Go', 'heading' => 'Editar destino']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $destino = $this->encontrarO404(Categoria::class, $id);

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect("/admin/destinos/{$id}/editar");
        }

        $slug = $destino['nombre'] === $datos['nombre']
            ? $destino['slug']
            : $this->slugUnico($datos['nombre'], $id);

        Categoria::update($id, [
            'nombre' => $datos['nombre'],
            'slug' => $slug,
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'] ?: null,
            'activo' => $datos['activo'],
        ]);

        $this->procesarImagen($id, $slug);
        Auditoria::registrar('destino.editar', 'destino', $id, $datos['nombre'] . ' (' . $datos['tipo'] . ')');

        Flash::set('exito', 'Destino actualizado correctamente.');
        $this->redirect('/admin/destinos');
    }

    public function alternarActivo(int $id): void
    {
        $this->verifyCsrf();

        $destino = Categoria::find($id);
        if ($destino !== false) {
            Categoria::update($id, ['activo' => $destino['activo'] ? 0 : 1]);
            Auditoria::registrar('destino.visible', 'destino', $id, $destino['activo'] ? 'oculto' : 'visible');
        }

        $this->redirect('/admin/destinos');
    }

    public function mover(int $id): void
    {
        $this->verifyCsrf();

        $destino = Categoria::find($id);
        $direccion = (string) $this->request->input('direccion', '');

        if ($destino !== false && in_array($direccion, ['arriba', 'abajo'], true)) {
            $lista = Categoria::adminListado();
            $posicion = array_search($id, array_map('intval', array_column($lista, 'id')), true);
            $vecino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;

            if ($posicion !== false && isset($lista[$vecino])) {
                Categoria::update((int) $lista[$posicion]['id'], ['orden' => $lista[$vecino]['orden']]);
                Categoria::update((int) $lista[$vecino]['id'], ['orden' => $lista[$posicion]['orden']]);
            }
        }

        $this->redirect('/admin/destinos');
    }

    public function eliminar(int $id): void
    {
        $this->verifyCsrf();

        $destino = $this->encontrarO404(Categoria::class, $id);

        // paquetes.categoria_id -> categorias(id) ON DELETE RESTRICT: la BD lo impediria de
        // todas formas; se corta antes para dar un mensaje claro. (articulos.categoria_id es
        // ON DELETE SET NULL, asi que un articulo enlazado solo pierde el destino, no se borra.)
        if (Categoria::tienePaquetes($id)) {
            Flash::set('error', 'No puedes eliminar un destino con paquetes asignados. Muevelos a otro destino o archivalos primero.');
            $this->redirect('/admin/destinos');
        }

        Categoria::delete($id);
        Auditoria::registrar('destino.eliminar', 'destino', $id, (string) ($destino['nombre'] ?? ''));

        Flash::set('exito', 'Destino eliminado.');
        $this->redirect('/admin/destinos');
    }

    /** @return array<string, mixed> */
    private function datosFormulario(): array
    {
        $datos = $this->request->only(['nombre', 'tipo', 'descripcion']);
        $datos['nombre'] = trim((string) ($datos['nombre'] ?? ''));
        $datos['descripcion'] = trim((string) ($datos['descripcion'] ?? ''));
        $datos['activo'] = $this->request->input('activo') ? 1 : 0;

        return $datos;
    }

    /** @param array<string, mixed> $datos */
    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')->maxLength('nombre', 100, 'El nombre')
            ->maxLength('descripcion', 1000, 'La descripcion');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');

            return false;
        }

        if (!array_key_exists($datos['tipo'] ?? '', self::TIPOS)) {
            Flash::set('error', 'Selecciona un tipo de destino valido.');

            return false;
        }

        return true;
    }

    private function slugUnico(string $nombre, ?int $ignorarId = null): string
    {
        $base = Slugify::generar($nombre, 'destino');
        $slug = $base;
        $intento = 1;

        while (true) {
            $existente = Categoria::first(['slug' => $slug]);
            if (!$existente || (int) $existente['id'] === $ignorarId) {
                return $slug;
            }
            $intento++;
            $slug = $base . '-' . $intento;
        }
    }

    private function procesarImagen(int $destinoId, string $slug): void
    {
        $archivo = $this->request->file('imagen');
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        try {
            $rutas = (new ImageUploadService())->procesar($archivo, $slug, 'destinos');
        } catch (\RuntimeException $e) {
            Flash::set('error', 'La imagen no se pudo procesar: ' . $e->getMessage());

            return;
        }

        Categoria::update($destinoId, ['imagen_portada' => $rutas['original']]);
    }
}
