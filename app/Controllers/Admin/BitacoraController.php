<?php

namespace App\Controllers\Admin;

use App\Models\Bitacora;

class BitacoraController extends AdminController
{
    public function index(): void
    {
        $accion = trim((string) $this->request->query('accion', ''));
        $accion = $accion !== '' ? $accion : null;

        $paginador = $this->paginar(Bitacora::contarTotal($accion));

        $this->view('admin/bitacora/index', [
            'registros' => Bitacora::adminListado($paginador->porPagina, $paginador->offset(), $accion),
            'paginador' => $paginador,
            'acciones' => Bitacora::accionesDistintas(),
            'accionActiva' => $accion,
        ], ['title' => 'Bitacora | Dream Go', 'heading' => 'Bitacora de acciones']);
    }
}
