<?php

namespace App\Controllers\Admin;

use App\Models\Suscriptor;
use Core\Response;

class SuscriptorAdminController extends AdminController
{
    public function index(): void
    {
        $paginador = $this->paginar(Suscriptor::contarTotal());

        $this->view('admin/suscriptores/index', [
            'suscriptores' => Suscriptor::adminListado($paginador->porPagina, $paginador->offset()),
            'paginador' => $paginador,
        ], ['title' => 'Suscriptores | Dream Go', 'heading' => 'Suscriptores al newsletter']);
    }

    public function exportarCsv(): void
    {
        $encabezados = ['Email', 'Estado', 'Fecha de alta', 'Confirmado en'];

        $filas = array_map(static fn (array $s): array => [
            $s['email'],
            $s['estado'],
            $s['creado_en'],
            $s['confirmado_en'] ?? '',
        ], Suscriptor::todosAdmin());

        Response::csv('suscriptores_' . date('Y-m-d') . '.csv', $encabezados, $filas);
    }
}
