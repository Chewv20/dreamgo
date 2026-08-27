<?php

namespace App\Controllers\Admin;

use App\Models\Cotizacion;
use App\Models\Reserva;
use App\Models\Salida;
use Core\Auth;
use Core\Database;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $db = Database::connection();

        [$desde, $hasta] = $this->rangoPeriodo();

        $datos = [
            'totalPaquetes' => (int) $db->query('SELECT COUNT(*) FROM paquetes')->fetchColumn(),
            'totalPaquetesPublicados' => (int) $db->query("SELECT COUNT(*) FROM paquetes WHERE estado = 'publicado'")->fetchColumn(),
            'periodoDesde' => $desde,
            'periodoHasta' => $hasta,
        ];

        if (Auth::hasPermission('cotizaciones.ver')) {
            $datos['totalCotizacionesNuevas'] = (int) $db->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'nueva'")->fetchColumn();
            $datos['conversion'] = Cotizacion::tasaConversionPeriodo($desde, $hasta);
            $datos['cotizacionesPorOrigen'] = Cotizacion::porOrigenPeriodo($desde, $hasta);
        }

        if (Auth::hasPermission('reservas.ver')) {
            $datos['totalReservasPendientes'] = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
            $datos['totalReservasConfirmadas'] = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada'")->fetchColumn();
            $datos['ingresosPeriodo'] = Reserva::ingresosPeriodo($desde, $hasta);
            $datos['proximasSalidas'] = Salida::proximasConOcupacion();
        }

        $this->view('admin/dashboard/index', $datos, ['title' => 'Panel | Dream Go', 'heading' => 'Panel']);
    }

    /**
     * Lee ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD de la query string y valida el formato con
     * preg_match para no dejar pasar basura a las consultas (aunque van parametrizadas,
     * strtotime() con entrada arbitraria produce fechas absurdas silenciosamente). Si falta,
     * es invalido, o desde > hasta, cae al default: primer dia del mes actual hasta hoy.
     */
    private function rangoPeriodo(): array
    {
        $formato = '/^\d{4}-\d{2}-\d{2}$/';

        $desde = (string) $this->request->query('desde', '');
        $hasta = (string) $this->request->query('hasta', '');

        $desdeValido = preg_match($formato, $desde) === 1 && strtotime($desde) !== false;
        $hastaValido = preg_match($formato, $hasta) === 1 && strtotime($hasta) !== false;

        if (!$desdeValido || !$hastaValido || $desde > $hasta) {
            $desde = date('Y-m-01');
            $hasta = date('Y-m-d');
        }

        return [$desde, $hasta];
    }
}
