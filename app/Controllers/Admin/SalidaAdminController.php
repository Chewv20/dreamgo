<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Paquete;
use App\Models\Salida;
use PDOException;

class SalidaAdminController extends AdminController
{
    private const ESTADOS = ['abierta', 'cerrada', 'cancelada'];

    public function index(int $paqueteId): void
    {
        $paquete = $this->encontrarO404(Paquete::class, $paqueteId);

        $this->view('admin/salidas/index', [
            'paquete' => $paquete,
            'salidas' => Salida::delPaquete($paqueteId),
        ], ['title' => 'Fechas de salida | Dream Go', 'heading' => 'Fechas y cupos: ' . $paquete['titulo']]);
    }

    public function crearForm(int $paqueteId): void
    {
        $paquete = $this->encontrarO404(Paquete::class, $paqueteId);

        $this->view('admin/salidas/create', [
            'paquete' => $paquete,
        ], ['title' => 'Nueva fecha | Dream Go', 'heading' => 'Nueva fecha de salida']);
    }

    public function crear(int $paqueteId): void
    {
        $this->verifyCsrf();

        $this->encontrarO404(Paquete::class, $paqueteId);

        $datos = $this->request->only(['fecha_salida', 'fecha_regreso', 'cupo_maximo', 'precio_override']);

        if (!$this->validar($datos)) {
            $this->redirect("/admin/paquetes/{$paqueteId}/salidas/crear");
        }

        try {
            Salida::insert([
                'paquete_id' => $paqueteId,
                'fecha_salida' => $datos['fecha_salida'],
                'fecha_regreso' => ($datos['fecha_regreso'] ?? '') !== '' ? $datos['fecha_regreso'] : null,
                'cupo_maximo' => (int) $datos['cupo_maximo'],
                'cupo_disponible' => (int) $datos['cupo_maximo'],
                'precio_override' => ($datos['precio_override'] ?? '') !== '' ? $datos['precio_override'] : null,
                'estado' => 'abierta',
            ]);
        } catch (PDOException $e) {
            error_log('[SalidaAdminController] Error de base de datos al crear salida: ' . $e->getMessage());
            Flash::set('error', 'No se pudo guardar la fecha de salida. Revisa los datos e intenta de nuevo.');
            $this->redirect("/admin/paquetes/{$paqueteId}/salidas/crear");
        }

        Flash::set('exito', 'Fecha de salida creada.');
        $this->redirect("/admin/paquetes/{$paqueteId}/salidas");
    }

    public function editarForm(int $paqueteId, int $id): void
    {
        $paquete = $this->encontrarO404(Paquete::class, $paqueteId);
        $salida = $this->encontrarO404(Salida::class, $id);

        $this->view('admin/salidas/edit', [
            'paquete' => $paquete,
            'salida' => $salida,
        ], ['title' => 'Editar fecha | Dream Go', 'heading' => 'Editar fecha de salida']);
    }

    public function editar(int $paqueteId, int $id): void
    {
        $this->verifyCsrf();

        $salida = $this->encontrarO404(Salida::class, $id);

        $datos = $this->request->only(['fecha_salida', 'fecha_regreso', 'cupo_maximo', 'precio_override', 'estado']);

        if (!$this->validar($datos)) {
            $this->redirect("/admin/paquetes/{$paqueteId}/salidas/{$id}/editar");
        }

        $cupoMaximo = (int) $datos['cupo_maximo'];
        $diferencia = $cupoMaximo - (int) $salida['cupo_maximo'];
        $nuevoDisponible = max(0, (int) $salida['cupo_disponible'] + $diferencia);

        try {
            Salida::update($id, [
                'fecha_salida' => $datos['fecha_salida'],
                'fecha_regreso' => ($datos['fecha_regreso'] ?? '') !== '' ? $datos['fecha_regreso'] : null,
                'cupo_maximo' => $cupoMaximo,
                'cupo_disponible' => min($nuevoDisponible, $cupoMaximo),
                'precio_override' => ($datos['precio_override'] ?? '') !== '' ? $datos['precio_override'] : null,
                'estado' => $datos['estado'] ?? 'abierta',
            ]);
        } catch (PDOException $e) {
            error_log('[SalidaAdminController] Error de base de datos al editar salida: ' . $e->getMessage());
            Flash::set('error', 'No se pudo guardar la fecha de salida. Revisa los datos e intenta de nuevo.');
            $this->redirect("/admin/paquetes/{$paqueteId}/salidas/{$id}/editar");
        }

        Flash::set('exito', 'Fecha de salida actualizada.');
        $this->redirect("/admin/paquetes/{$paqueteId}/salidas");
    }

    /**
     * Auditoria 2026-08-31, hallazgo BD-01: antes solo se exigia fecha_salida y un cupo_maximo
     * entero. Una fecha mal formada, un precio no numerico o un estado fuera del ENUM llegaban
     * al INSERT/UPDATE y salian como 500 generico (PDOException sin capturar) en vez de un
     * mensaje al operador.
     *
     * @param array<string, mixed> $datos
     */
    private function validar(array $datos): bool
    {
        $validator = new Validator($datos);
        $validator->requerido('fecha_salida', 'La fecha de salida')->fecha('fecha_salida', 'La fecha de salida')
            ->fecha('fecha_regreso', 'La fecha de regreso')
            ->requerido('cupo_maximo', 'El cupo maximo')->entero('cupo_maximo', 'El cupo maximo')
            ->enRango('cupo_maximo', 1, 65535, 'El cupo maximo');

        if (!$validator->pasa()) {
            Flash::set('error', 'Revisa los datos del formulario.');

            return false;
        }

        if (($datos['fecha_regreso'] ?? '') !== '' && $datos['fecha_regreso'] < $datos['fecha_salida']) {
            Flash::set('error', 'La fecha de regreso no puede ser anterior a la fecha de salida.');

            return false;
        }

        if (($datos['precio_override'] ?? '') !== ''
            && (!is_numeric($datos['precio_override']) || (float) $datos['precio_override'] < 0)) {
            Flash::set('error', 'El precio específico debe ser un número mayor o igual a cero.');

            return false;
        }

        if (isset($datos['estado']) && !in_array($datos['estado'], self::ESTADOS, true)) {
            Flash::set('error', 'Selecciona un estado válido.');

            return false;
        }

        return true;
    }
}
