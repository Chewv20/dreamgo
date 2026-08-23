<?php

namespace App\Controllers\Admin;

use Core\Auth;
use Core\Database;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $db = Database::connection();

        $datos = [
            'totalPaquetes' => (int) $db->query('SELECT COUNT(*) FROM paquetes')->fetchColumn(),
            'totalPaquetesPublicados' => (int) $db->query("SELECT COUNT(*) FROM paquetes WHERE estado = 'publicado'")->fetchColumn(),
        ];

        if (Auth::hasPermission('cotizaciones.ver')) {
            $datos['totalCotizacionesNuevas'] = (int) $db->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'nueva'")->fetchColumn();
        }

        if (Auth::hasPermission('reservas.ver')) {
            $datos['totalReservasPendientes'] = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
            $datos['totalReservasConfirmadas'] = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada'")->fetchColumn();
        }

        $this->view('admin/dashboard/index', $datos, ['title' => 'Panel | Dream Go', 'heading' => 'Panel']);
    }
}
