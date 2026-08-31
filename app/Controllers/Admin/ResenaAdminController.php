<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Models\Resena;

class ResenaAdminController extends AdminController
{
    public function index(): void
    {
        $paginador = $this->paginar(Resena::contarTotal());

        $this->view('admin/resenas/index', [
            'resenas' => Resena::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Resenas | Dream Go', 'heading' => 'Resenas de clientes']);
    }

    public function cambiarEstado(int $id): void
    {
        $this->verifyCsrf();

        if (!Resena::find($id)) {
            $this->abort(404);
        }

        $estado = (string) $this->request->input('estado');
        if (!in_array($estado, ['pendiente', 'aprobada', 'rechazada'], true)) {
            $this->abort(404);
        }

        Resena::update($id, ['estado' => $estado, 'moderada_en' => date('Y-m-d H:i:s')]);
        Auditoria::registrar('resena.estado', 'resena', $id, 'estado -> ' . $estado);
        Flash::set('exito', 'Estado de la resena actualizado.');
        $this->redirect('/admin/resenas');
    }
}
