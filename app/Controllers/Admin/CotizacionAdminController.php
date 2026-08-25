<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\Cotizacion;
use Core\Response;

class CotizacionAdminController extends AdminController
{
    public function index(): void
    {
        $paginador = $this->paginar(Cotizacion::contarTotal());

        $this->view('admin/cotizaciones/index', [
            'cotizaciones' => Cotizacion::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Cotizaciones | Dream Go', 'heading' => 'Cotizaciones']);
    }

    public function exportarCsv(): void
    {
        $encabezados = ['Fecha', 'Nombre', 'Email', 'Telefono', 'Paquete', 'Personas', 'Fecha tentativa', 'Mensaje', 'Estado'];

        $filas = array_map(static fn (array $c): array => [
            $c['creado_en'],
            $c['nombre'],
            $c['email'],
            $c['telefono'],
            $c['paquete_titulo'] ?? 'General',
            $c['num_personas'],
            $c['fecha_tentativa'] ?? '',
            $c['mensaje'],
            $c['estado'],
        ], Cotizacion::todasAdmin());

        Response::csv('cotizaciones_' . date('Y-m-d') . '.csv', $encabezados, $filas);
    }

    public function cambiarEstado(int $id): void
    {
        $this->verifyCsrf();

        if (!Cotizacion::find($id)) {
            $this->abort(404);
        }

        $estado = (string) $this->request->input('estado');
        if (!in_array($estado, ['nueva', 'contactada', 'convertida', 'descartada'], true)) {
            $this->abort(404);
        }

        Cotizacion::update($id, ['estado' => $estado]);
        Flash::set('exito', 'Estado actualizado.');
        $this->redirect('/admin/cotizaciones');
    }
}
