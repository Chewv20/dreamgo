<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\Cotizacion;
use Core\Paginator;

class CotizacionAdminController extends AdminController
{
    private const POR_PAGINA = 20;

    public function index(): void
    {
        $paginador = new Paginator(Paginator::paginaDesde($this->request), self::POR_PAGINA, Cotizacion::contarTotal());

        $this->view('admin/cotizaciones/index', [
            'cotizaciones' => Cotizacion::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Cotizaciones | Dream Go', 'heading' => 'Cotizaciones']);
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
