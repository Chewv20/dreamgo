<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\Validator;
use App\Models\Paquete;
use App\Models\Salida;

class SalidaAdminController extends AdminController
{
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

        $paquete = $this->encontrarO404(Paquete::class, $paqueteId);

        $datos = $this->request->only(['fecha_salida', 'fecha_regreso', 'cupo_maximo', 'precio_override']);

        $validator = new Validator($datos);
        $validator->requerido('fecha_salida', 'La fecha de salida')
            ->requerido('cupo_maximo', 'El cupo maximo')
            ->entero('cupo_maximo', 'El cupo maximo');

        $this->redirigirSiInvalido($validator, "/admin/paquetes/{$paqueteId}/salidas/crear");

        Salida::insert([
            'paquete_id' => $paqueteId,
            'fecha_salida' => $datos['fecha_salida'],
            'fecha_regreso' => $datos['fecha_regreso'] ?: null,
            'cupo_maximo' => (int) $datos['cupo_maximo'],
            'cupo_disponible' => (int) $datos['cupo_maximo'],
            'precio_override' => $datos['precio_override'] !== '' ? $datos['precio_override'] : null,
            'estado' => 'abierta',
        ]);

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

        $cupoMaximo = (int) $this->request->input('cupo_maximo');
        $diferencia = $cupoMaximo - (int) $salida['cupo_maximo'];
        $nuevoDisponible = max(0, (int) $salida['cupo_disponible'] + $diferencia);

        Salida::update($id, [
            'fecha_salida' => $this->request->input('fecha_salida'),
            'fecha_regreso' => $this->request->input('fecha_regreso') ?: null,
            'cupo_maximo' => $cupoMaximo,
            'cupo_disponible' => min($nuevoDisponible, $cupoMaximo),
            'precio_override' => $this->request->input('precio_override') !== '' ? $this->request->input('precio_override') : null,
            'estado' => $this->request->input('estado', 'abierta'),
        ]);

        Flash::set('exito', 'Fecha de salida actualizada.');
        $this->redirect("/admin/paquetes/{$paqueteId}/salidas");
    }
}
