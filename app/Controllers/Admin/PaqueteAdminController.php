<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Helpers\HtmlSanitizer;
use App\Helpers\Slugify;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;
use App\Models\Categoria;
use App\Models\ImagenPaquete;
use App\Models\Paquete;
use App\Services\ImageUploadService;
use Core\Auth;

class PaqueteAdminController extends AdminController
{
    private const MONEDAS = [
        'MXN' => 'Peso mexicano (MXN)',
        'USD' => 'Dolar estadounidense (USD)',
        'EUR' => 'Euro (EUR)',
        'CAD' => 'Dolar canadiense (CAD)',
        'GBP' => 'Libra esterlina (GBP)',
    ];

    public function index(): void
    {
        $paginador = $this->paginar(Paquete::contarTotal());

        $this->view('admin/paquetes/index', [
            'paquetes' => Paquete::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Paquetes | Dream Go', 'heading' => 'Paquetes']);
    }

    public function crearForm(): void
    {
        $this->view('admin/paquetes/create', [
            'categorias' => $this->categoriasSeleccionables(),
            'monedas' => self::MONEDAS,
            'monedaBloqueada' => false,
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
            'moneda' => $datos['moneda'],
            'duracion_dias' => $datos['duracion_dias'] ?: null,
            'duracion_noches' => $datos['duracion_noches'] ?: null,
            'destacado' => $datos['destacado'],
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
            'creado_por' => Auth::id(),
        ]);

        $this->procesarGaleria($id, $slug);

        Auditoria::registrar('paquete.crear', 'paquete', $id, $datos['titulo'] . ' (' . $datos['estado'] . ')');

        Flash::set('exito', 'Paquete creado correctamente.');
        $this->redirect('/admin/paquetes');
    }

    public function editarForm(int $id): void
    {
        $paquete = $this->encontrarO404(Paquete::class, $id);

        $this->view('admin/paquetes/edit', [
            'paquete' => $paquete,
            'categorias' => $this->categoriasSeleccionables($paquete),
            'imagenes' => Paquete::imagenes($id),
            'monedas' => self::MONEDAS,
            'monedaBloqueada' => Paquete::tieneReservas($id),
        ], ['title' => 'Editar paquete | Dream Go', 'heading' => 'Editar paquete']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $paquete = $this->encontrarO404(Paquete::class, $id);

        $datos = $this->datosFormulario();

        // Un paquete con reservas no puede cambiar de moneda (ver Paquete::tieneReservas):
        // se ignora cualquier valor recibido y se conserva el actual, aunque el campo del
        // formulario venga manipulado.
        if (Paquete::tieneReservas($id)) {
            $datos['moneda'] = $paquete['moneda'];
        }

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
            'moneda' => $datos['moneda'],
            'duracion_dias' => $datos['duracion_dias'] ?: null,
            'duracion_noches' => $datos['duracion_noches'] ?: null,
            'destacado' => $datos['destacado'],
            'estado' => $datos['estado'],
            'meta_title' => $datos['meta_title'] ?: null,
            'meta_description' => $datos['meta_description'] ?: null,
        ]);

        $this->procesarGaleria($id, $slug);

        Auditoria::registrar('paquete.editar', 'paquete', $id, $datos['titulo'] . ' (' . $datos['estado'] . ')');

        Flash::set('exito', 'Paquete actualizado correctamente.');
        $this->redirect('/admin/paquetes');
    }

    public function eliminarImagen(int $id, int $imagenId): void
    {
        $this->verifyCsrf();

        $paquete = $this->encontrarO404(Paquete::class, $id);

        if (!ImagenPaquete::pertenece($imagenId, $id)) {
            $this->abort(404);
        }

        $imagen = ImagenPaquete::find($imagenId);
        ImagenPaquete::delete($imagenId);
        // Best-effort: si el archivo ya no esta en disco no es un error, la fila igual se borra.
        @unlink(BASE_PATH . '/public' . $imagen['ruta_original']);
        @unlink(BASE_PATH . '/public' . $imagen['ruta_thumb']);

        Paquete::sincronizarPortada($id);
        Auditoria::registrar('paquete.imagen_eliminar', 'paquete', $id, (string) ($paquete['titulo'] ?? ''));

        Flash::set('exito', 'Imagen eliminada.');
        $this->redirect("/admin/paquetes/{$id}/editar");
    }

    public function moverImagen(int $id, int $imagenId): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(Paquete::class, $id);
        $direccion = (string) $this->request->input('direccion', '');

        if (ImagenPaquete::pertenece($imagenId, $id) && in_array($direccion, ['arriba', 'abajo'], true)) {
            ImagenPaquete::mover($imagenId, $id, $direccion);
            Paquete::sincronizarPortada($id);
        }

        $this->redirect("/admin/paquetes/{$id}/editar");
    }

    public function archivar(int $id): void
    {
        $this->verifyCsrf();

        $paquete = $this->encontrarO404(Paquete::class, $id);

        Paquete::update($id, ['estado' => 'archivado']);
        Auditoria::registrar('paquete.archivar', 'paquete', $id, (string) ($paquete['titulo'] ?? ''));
        Flash::set('exito', 'Paquete archivado.');
        $this->redirect('/admin/paquetes');
    }

    /**
     * Opciones del <select> "Categoria / destino" del formulario: solo destinos visibles, para
     * no asignar paquetes nuevos a un destino oculto. Al editar, si el paquete ya apunta a un
     * destino oculto se agrega esa opcion igual, para no perder la asignacion al guardar.
     *
     * @param array<string, mixed>|null $paquete
     * @return list<array<string, mixed>>
     */
    private function categoriasSeleccionables(?array $paquete = null): array
    {
        $categorias = Categoria::activas();

        $actualId = (int) ($paquete['categoria_id'] ?? 0);
        $yaEsta = in_array($actualId, array_map(static fn ($c) => (int) $c['id'], $categorias), true);

        if ($actualId > 0 && !$yaEsta) {
            $actual = Categoria::find($actualId);
            if ($actual !== false) {
                $categorias[] = $actual;
            }
        }

        return $categorias;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        $datos = $this->request->only([
            'categoria_id', 'titulo', 'resumen', 'descripcion_larga', 'itinerario',
            'incluye', 'no_incluye', 'precio_desde', 'moneda', 'duracion_dias', 'duracion_noches',
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
            Flash::set('error', 'El precio debe ser un número válido.');

            return false;
        }

        // El <select> solo ofrece destinos validos, pero un POST manipulado con un id
        // inexistente reventaria contra la FK (error 500) en vez de dar un mensaje claro.
        if ($validator->pasa() && Categoria::find((int) $datos['categoria_id']) === false) {
            Flash::set('error', 'El destino seleccionado no existe.');

            return false;
        }

        if (!array_key_exists($datos['moneda'] ?? '', self::MONEDAS)) {
            Flash::set('error', 'Selecciona una moneda válida.');

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
        $base = Slugify::generar($titulo, 'paquete');
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

    /**
     * Sube todas las imagenes nuevas seleccionadas (name="imagenes[]") a la galeria del
     * paquete y sincroniza imagen_portada. Si algun archivo del lote no se pudo procesar
     * (formato invalido, etc.) se acumula el mensaje pero se sigue con los demas.
     */
    private function procesarGaleria(int $paqueteId, string $slug): void
    {
        $archivos = UploadHelper::listaArchivos($this->request->file('imagenes'));
        if ($archivos === []) {
            return;
        }

        $errores = [];
        foreach ($archivos as $archivo) {
            try {
                $rutas = (new ImageUploadService())->procesar($archivo, $slug);
                ImagenPaquete::agregar($paqueteId, $rutas['original'], $rutas['thumb'], null);
            } catch (\RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }

        Paquete::sincronizarPortada($paqueteId);

        if ($errores !== []) {
            Flash::set('error', 'Algunas imagenes no se pudieron procesar: ' . implode(' ', $errores));
        }
    }
}
