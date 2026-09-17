<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\Slugify;
use App\Helpers\Validator;
use App\Models\ModalPromocion;
use App\Models\Paquete;
use App\Services\ImageUploadService;

/**
 * CRUD de modales promocionales (tabla `modales_promocion`). Mismo patron que destinos:
 * slug de imagen unico, imagen de portada reencodeada por ImageUploadService y bitacora en
 * cada mutacion. El destino del boton es siempre un paquete (enlaza a su ficha publica).
 */
class ModalAdminController extends AdminController
{
    private const ALCANCES = ['inicio' => 'Solo en el inicio', 'todas' => 'En todo el sitio'];

    public function index(): void
    {
        $this->view('admin/modales/index', [
            'modales' => ModalPromocion::adminListado(),
            'alcances' => self::ALCANCES,
        ], ['title' => 'Modales | Dream Go', 'heading' => 'Modales promocionales']);
    }

    public function crearForm(): void
    {
        $this->view('admin/modales/create', [
            'paquetes' => Paquete::all('titulo ASC'),
            'alcances' => self::ALCANCES,
        ], ['title' => 'Nuevo modal | Dream Go', 'heading' => 'Nuevo modal promocional']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect('/admin/modales/crear');
        }

        $id = ModalPromocion::insert($this->paraGuardar($datos));

        $this->procesarImagen($id, Slugify::generar($datos['titulo'], 'modal'));
        Auditoria::registrar('modal.crear', 'modal', $id, $datos['titulo']);

        Flash::set('exito', 'Modal creado correctamente.');
        $this->redirect('/admin/modales');
    }

    public function editarForm(int $id): void
    {
        $modal = $this->encontrarO404(ModalPromocion::class, $id);
        $paqueteActual = Paquete::find((int) $modal['paquete_id']);

        $this->view('admin/modales/edit', [
            'modal' => $modal,
            'paqueteActual' => $paqueteActual !== false ? $paqueteActual : null,
            'paquetes' => Paquete::all('titulo ASC'),
            'alcances' => self::ALCANCES,
        ], ['title' => 'Editar modal | Dream Go', 'heading' => 'Editar modal promocional']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(ModalPromocion::class, $id);

        $datos = $this->datosFormulario();
        if (!$this->validar($datos)) {
            $this->redirect("/admin/modales/{$id}/editar");
        }

        ModalPromocion::update($id, $this->paraGuardar($datos));

        $this->procesarImagen($id, Slugify::generar($datos['titulo'], 'modal'));
        Auditoria::registrar('modal.editar', 'modal', $id, $datos['titulo']);

        Flash::set('exito', 'Modal actualizado correctamente.');
        $this->redirect('/admin/modales');
    }

    public function alternarActivo(int $id): void
    {
        $this->verifyCsrf();

        $modal = ModalPromocion::find($id);
        if ($modal !== false) {
            ModalPromocion::update($id, ['activo' => $modal['activo'] ? 0 : 1]);
            Auditoria::registrar('modal.visible', 'modal', $id, $modal['activo'] ? 'oculto' : 'visible');
        }

        $this->redirect('/admin/modales');
    }

    public function eliminar(int $id): void
    {
        $this->verifyCsrf();

        $modal = $this->encontrarO404(ModalPromocion::class, $id);

        ModalPromocion::delete($id);
        Auditoria::registrar('modal.eliminar', 'modal', $id, (string) ($modal['titulo'] ?? ''));

        Flash::set('exito', 'Modal eliminado.');
        $this->redirect('/admin/modales');
    }

    /** @return array<string, mixed> */
    private function datosFormulario(): array
    {
        $datos = $this->request->only(['titulo', 'texto_boton', 'cuerpo', 'mostrar_en', 'fecha_inicio', 'fecha_fin']);
        $datos['titulo'] = trim((string) ($datos['titulo'] ?? ''));
        $datos['texto_boton'] = trim((string) ($datos['texto_boton'] ?? '')) ?: 'Ver paquete';
        $datos['cuerpo'] = trim((string) ($datos['cuerpo'] ?? ''));
        $datos['mostrar_en'] = (string) ($datos['mostrar_en'] ?? 'inicio');
        $datos['fecha_inicio'] = trim((string) ($datos['fecha_inicio'] ?? ''));
        $datos['fecha_fin'] = trim((string) ($datos['fecha_fin'] ?? ''));
        $datos['paquete_id'] = (int) $this->request->input('paquete_id', 0);
        $datos['prioridad'] = max(0, (int) $this->request->input('prioridad', 0));
        $datos['activo'] = $this->request->input('activo') ? 1 : 0;

        return $datos;
    }

    /**
     * Traduce los datos del formulario a las columnas de la tabla (fechas y cuerpo vacios
     * se guardan como NULL). La imagen no va aqui: la fija procesarImagen() aparte.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function paraGuardar(array $datos): array
    {
        return [
            'titulo' => $datos['titulo'],
            'cuerpo' => $datos['cuerpo'] !== '' ? $datos['cuerpo'] : null,
            'texto_boton' => $datos['texto_boton'],
            'paquete_id' => $datos['paquete_id'],
            'mostrar_en' => $datos['mostrar_en'],
            'fecha_inicio' => $datos['fecha_inicio'] !== '' ? $datos['fecha_inicio'] : null,
            'fecha_fin' => $datos['fecha_fin'] !== '' ? $datos['fecha_fin'] : null,
            'prioridad' => $datos['prioridad'],
            'activo' => $datos['activo'],
        ];
    }

    /** @param array<string, mixed> $datos */
    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('titulo', 'El título')->maxLength('titulo', 120, 'El título')
            ->maxLength('texto_boton', 40, 'El texto del botón')
            ->maxLength('cuerpo', 500, 'El cuerpo')
            ->fecha('fecha_inicio', 'La fecha de inicio')
            ->fecha('fecha_fin', 'La fecha de fin');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');

            return false;
        }

        if (!array_key_exists($datos['mostrar_en'], self::ALCANCES)) {
            Flash::set('error', 'Selecciona dónde debe mostrarse el modal.');

            return false;
        }

        if ($datos['paquete_id'] <= 0 || Paquete::find($datos['paquete_id']) === false) {
            Flash::set('error', 'Selecciona el paquete al que llevará el botón.');

            return false;
        }

        if ($datos['fecha_inicio'] !== '' && $datos['fecha_fin'] !== '' && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            Flash::set('error', 'La fecha de fin no puede ser anterior a la fecha de inicio.');

            return false;
        }

        return true;
    }

    private function procesarImagen(int $modalId, string $slug): void
    {
        $archivo = $this->request->file('imagen');
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        try {
            $rutas = (new ImageUploadService())->procesar($archivo, $slug, 'modales');
        } catch (\RuntimeException $e) {
            Flash::set('error', 'La imagen no se pudo procesar: ' . $e->getMessage());

            return;
        }

        ModalPromocion::update($modalId, ['imagen' => $rutas['original']]);
    }
}
