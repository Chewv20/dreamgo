<?php

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Models\Cotizacion;
use Core\Response;

class CotizacionAdminController extends AdminController
{
    public function index(): void
    {
        $origen = trim((string) $this->request->query('origen', ''));
        $origen = $origen !== '' ? $origen : null;

        $paginador = $this->paginar(Cotizacion::contarTotal($origen));

        $this->view('admin/cotizaciones/index', [
            'cotizaciones' => Cotizacion::adminListado($paginador->porPagina, $paginador->offset(), $origen),
            'paginador' => $paginador,
            'fuentes' => Cotizacion::fuentesDistintas(),
            'origenActivo' => $origen,
        ], ['title' => 'Cotizaciones | Dream Go', 'heading' => 'Cotizaciones']);
    }

    public function exportarCsv(): void
    {
        $encabezados = [
            'Fecha', 'Nombre', 'Email', 'Telefono', 'Paquete', 'Personas', 'Fecha tentativa', 'Mensaje', 'Estado',
            'UTM source', 'UTM medium', 'UTM campaign', 'UTM term', 'UTM content', 'Referrer', 'Landing page',
        ];

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
            $c['utm_source'] ?? '',
            $c['utm_medium'] ?? '',
            $c['utm_campaign'] ?? '',
            $c['utm_term'] ?? '',
            $c['utm_content'] ?? '',
            $c['referrer'] ?? '',
            $c['landing_page'] ?? '',
        ], Cotizacion::todasAdmin());

        Response::csv('cotizaciones_' . date('Y-m-d') . '.csv', $encabezados, $filas);
    }

    public function cambiarEstado(int $id): void
    {
        $this->verifyCsrf();

        $cotizacion = Cotizacion::find($id);
        if (!$cotizacion) {
            $this->abort(404);
        }

        $estado = (string) $this->request->input('estado');
        if (!in_array($estado, ['nueva', 'contactada', 'convertida', 'descartada'], true)) {
            $this->abort(404);
        }

        Cotizacion::update($id, ['estado' => $estado]);
        Auditoria::registrar('cotizacion.estado', 'cotizacion', $id, ($cotizacion['estado'] ?? '?') . ' -> ' . $estado);
        Flash::set('exito', 'Estado actualizado.');
        $this->redirect('/admin/cotizaciones');
    }
}
