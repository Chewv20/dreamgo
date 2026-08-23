<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\Cotizacion;

class CotizacionAdminController extends AdminController
{
    public function index(): void
    {
        $stmt = $this->db->query(
            'SELECT c.*, p.titulo AS paquete_titulo
             FROM cotizaciones c
             LEFT JOIN paquetes p ON p.id = c.paquete_id
             ORDER BY c.creado_en DESC'
        );

        $this->view('admin/cotizaciones/index', [
            'cotizaciones' => $stmt->fetchAll(),
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
